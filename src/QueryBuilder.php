<?php

/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace edgardmessias\db\informix;

use yii\base\InvalidArgumentException;
use yii\base\NotSupportedException;
use yii\db\Exception;
use yii\db\ExpressionInterface;
use yii\db\Query;

/**
 * @author Edgard Messias <edgardmessias@gmail.com>
 * @since 1.0
 */
class QueryBuilder extends \yii\db\QueryBuilder
{

    /**
     * @var array mapping from abstract column types (keys) to physical column types (values).
     */
    public $typeMap = [
        Schema::TYPE_PK        => 'serial NOT NULL PRIMARY KEY',
        Schema::TYPE_BIGPK     => 'serial8 NOT NULL PRIMARY KEY',
        Schema::TYPE_STRING    => 'varchar(255)',
        Schema::TYPE_TEXT      => 'text',
        Schema::TYPE_SMALLINT  => 'smallint',
        Schema::TYPE_INTEGER   => 'integer',
        Schema::TYPE_BIGINT    => 'bigint',
        Schema::TYPE_FLOAT     => 'smallfloat',
        Schema::TYPE_DOUBLE    => 'float',
        Schema::TYPE_DECIMAL   => 'decimal(10,0)',
        Schema::TYPE_DATETIME  => 'datetime year to second',
        Schema::TYPE_TIMESTAMP => 'datetime year to second',
        Schema::TYPE_TIME      => 'datetime hour to second',
        Schema::TYPE_DATE      => 'datetime year to day',
        Schema::TYPE_BINARY    => 'blob',
        Schema::TYPE_BOOLEAN   => 'boolean',
        Schema::TYPE_MONEY     => 'money(19,4)',
    ];

    /**
     * {@inheritdoc}
     */
    protected function defaultExpressionBuilders()
    {
        return array_merge(parent::defaultExpressionBuilders(), [
            'yii\db\conditions\InCondition' => 'edgardmessias\db\informix\conditions\InConditionBuilder',
            'yii\db\conditions\LikeCondition' => 'edgardmessias\db\ibm\db2\conditions\LikeConditionBuilder',
        ]);
    }

    /**
     * Generates a SELECT SQL statement from a [[Query]] object.
     * @param Query $query the [[Query]] object from which the SQL statement will be generated.
     * @param array $params the parameters to be bound to the generated SQL statement. These parameters will
     * be included in the result with the additional parameters generated during the query building process.
     * @return array the generated SQL statement (the first array element) and the corresponding
     * parameters to be bound to the SQL statement (the second array element). The parameters returned
     * include those provided in `$params`.
     * @throws Exception
     */
    public function build($query, $params = [])
    {
        $query = $query->prepare($this);

        $params = empty($params) ? $query->params : array_merge($params, $query->params);

        $clauses = [
            $this->buildSelect($query->select, $params, $query->distinct, $query->selectOption),
            $this->buildFrom($query->from, $params),
            $this->buildJoin($query->join, $params),
            $this->buildWhere($query->where, $params),
            $this->buildGroupBy($query->groupBy),
            $this->buildHaving($query->having, $params),
        ];

        $sql = implode($this->separator, array_filter($clauses));
        $sql = $this->buildOrderByAndLimit($sql, $query->orderBy, $query->limit, $query->offset);

        if (!empty($query->orderBy)) {
            foreach ($query->orderBy as $expression) {
                if ($expression instanceof ExpressionInterface) {
                    $this->buildExpression($expression, $params);
                }
            }
        }
        if (!empty($query->groupBy)) {
            foreach ($query->groupBy as $expression) {
                if ($expression instanceof ExpressionInterface) {
                    $this->buildExpression($expression, $params);
                }
            }
        }

        $union = $this->buildUnion($query->union, $params);
        if ($union !== '') {
            $prefix = '';
            if($query->limit || $query->offset){
                $prefix = "SELECT * FROM ";
            }
            $sql = "$prefix($sql){$this->separator}$union";
        }
        
        foreach ($params as $k => $v) {
            if (is_bool($v)) {
                $params[$k] = $v ? 1 : 0;
            }
        }
        
        return [$sql, $params];
    }

    /**
     * Creates an INSERT SQL statement.
     * For example,
     *
     * ~~~
     * $sql = $queryBuilder->insert('user', [
     *  'name' => 'Sam',
     *  'age' => 30,
     * ], $params);
     * ~~~
     *
     * The method will properly escape the table and column names.
     *
     * @param string $table the table that new rows will be inserted into.
     * @param array $columns the column data (name => value) to be inserted into the table.
     * @param array $params the binding parameters that will be generated by this method.
     * They should be bound to the DB command later.
     * @return string the INSERT SQL
     * @throws NotSupportedException
     */
    public function insert($table, $columns, &$params)
    {
        if (empty($columns)) {
            $schema = $this->db->getSchema();
            if (($tableSchema = $schema->getTableSchema($table)) !== null && $tableSchema->sequenceName !== null) {
                $columns[$tableSchema->sequenceName] = 0;
            }
        }
        
        return parent::insert($table, $columns, $params);
    }

    /**
     * Generates a batch INSERT SQL statement.
     * For example,
     *
     * ~~~
     * $connection->createCommand()->batchInsert('user', ['name', 'age'], [
     *     ['Tom', 30],
     *     ['Jane', 20],
     *     ['Linda', 25],
     * ])->execute();
     * ~~~
     *
     * Note that the values in each row must match the corresponding column names.
     *
     * @param string $table the table that new rows will be inserted into.
     * @param array $columns the column names
     * @param array $rows the rows to be batch inserted into the table
     * @param array $params
     * @return string the batch INSERT SQL statement
     * @throws NotSupportedException
     */
    public function batchInsert($table, $columns, $rows, &$params = [])
    {
        $schema = $this->db->getSchema();
        if (($tableSchema = $schema->getTableSchema($table)) !== null) {
            $columnSchemas = $tableSchema->columns;
        } else {
            $columnSchemas = [];
        }

        $values = [];
        foreach ($rows as $row) {
            $vs = [];
            foreach ($row as $i => $value) {
                if (!is_array($value) && isset($columnSchemas[$columns[$i]])) {
                    $value = $columnSchemas[$columns[$i]]->dbTypecast($value);
                }
                if (is_string($value)) {
                    $value = $schema->quoteValue($value);
                } elseif ($value === false) {
                    $value = 0;
                } elseif ($value === null) {
                    $value = 'NULL';
                    if (isset($columnSchemas[$columns[$i]])) {
                        $value.= '::' . $columnSchemas[$columns[$i]]->dbType;
                    } else {
                        $value.= '::char';
                    }
                } elseif ($value instanceof ExpressionInterface) {
                    $value = $this->buildExpression($value, $params);
                }
                $vs[] = $value;
            }
            $values[] = 'SELECT ' . implode(', ', $vs) . ' FROM TABLE(set{1})';
        }

        if (empty($values)) {
            return '';
        }

        foreach ($columns as $i => $name) {
            $columns[$i] = $schema->quoteColumnName($name);
        }

        return 'INSERT INTO ' . $schema->quoteTableName($table)
                . ' (' . implode(', ', $columns) . ') SELECT * FROM (' . implode(' UNION ALL ', $values) . ')';
    }

    /**
     * Builds a SQL statement for adding a primary key constraint to an existing table.
     * @param string $name the name of the primary key constraint.
     * @param string $table the table that the primary key constraint will be added to.
     * @param string|array $columns comma separated string or array of columns that the primary key will consist of.
     * @return string the SQL statement for adding a primary key constraint to an existing table.
     */
    public function addPrimaryKey($name, $table, $columns)
    {
        if (is_string($columns)) {
            $columns = preg_split('/\s*,\s*/', $columns, -1, PREG_SPLIT_NO_EMPTY);
        }

        foreach ($columns as $i => $col) {
            $columns[$i] = $this->db->quoteColumnName($col);
        }

        return 'ALTER TABLE ' . $this->db->quoteTableName($table)
            .  ' ADD CONSTRAINT PRIMARY KEY ('
            . implode(', ', $columns). ' ) CONSTRAINT ' . $this->db->quoteColumnName($name);
    }

    /**
     * Builds a SQL statement for dropping a DB column.
     * @param string $table the table whose column is to be dropped. The name will be properly quoted by the method.
     * @param string $column the name of the column to be dropped. The name will be properly quoted by the method.
     * @return string the SQL statement for dropping a DB column.
     */
    public function dropColumn($table, $column)
    {
        return "ALTER TABLE " . $this->db->quoteTableName($table)
            . " DROP " . $this->db->quoteColumnName($column);
    }

    /**
     * Builds a SQL statement for renaming a column.
     * @param string $table the table whose column is to be renamed. The name will be properly quoted by the method.
     * @param string $oldName the old name of the column. The name will be properly quoted by the method.
     * @param string $newName the new name of the column. The name will be properly quoted by the method.
     * @return string the SQL statement for renaming a DB column.
     */
    public function renameColumn($table, $oldName, $newName)
    {
        return "RENAME COLUMN " . $this->db->quoteTableName($table) . "." . $this->db->quoteColumnName($oldName)
            . " TO " . $this->db->quoteColumnName($newName);
    }

    /**
     * Builds a SQL statement for changing the definition of a column.
     * @param string $table the table whose column is to be changed. The table name will be properly quoted by the method.
     * @param string $column the name of the column to be changed. The name will be properly quoted by the method.
     * @param string $type the new column type. The [[getColumnType()]] method will be invoked to convert abstract
     * column type (if any) into the physical one. Anything that is not recognized as abstract type will be kept
     * in the generated SQL. For example, 'string' will be turned into 'varchar(255)', while 'string not null'
     * will become 'varchar(255) not null'.
     * @return string the SQL statement for changing the definition of a column.
     */
    public function alterColumn($table, $column, $type)
    {
        return 'ALTER TABLE ' . $this->db->quoteTableName($table) . ' MODIFY ('
                . $this->db->quoteColumnName($column) . ' '
                . $this->getColumnType($type) . ')';
    }

    /**
     * Builds a SQL statement for adding a foreign key constraint to an existing table.
     * The method will properly quote the table and column names.
     * @param string $name the name of the foreign key constraint.
     * @param string $table the table that the foreign key constraint will be added to.
     * @param string|array $columns the name of the column to that the constraint will be added on.
     * If there are multiple columns, separate them with commas or use an array to represent them.
     * @param string $refTable the table that the foreign key references to.
     * @param string|array $refColumns the name of the column that the foreign key references to.
     * If there are multiple columns, separate them with commas or use an array to represent them.
     * @param string $delete the ON DELETE option. Most DBMS support these options: RESTRICT, CASCADE, NO ACTION, SET DEFAULT, SET NULL
     * @param string $update the ON UPDATE option. Most DBMS support these options: RESTRICT, CASCADE, NO ACTION, SET DEFAULT, SET NULL
     * @return string the SQL statement for adding a foreign key constraint to an existing table.
     */
    public function addForeignKey($name, $table, $columns, $refTable, $refColumns, $delete = null, $update = null)
    {
        $sql = 'ALTER TABLE ' . $this->db->quoteTableName($table)
            . ' ADD CONSTRAINT FOREIGN KEY (' . $this->buildColumns($columns) . ')'
            . ' REFERENCES ' . $this->db->quoteTableName($refTable)
            . ' (' . $this->buildColumns($refColumns) . ')'
            . ' CONSTRAINT ' . $this->db->quoteColumnName($name);
        if ($delete !== null) {
            $sql .= ' ON DELETE ' . $delete;
        }
        if ($update !== null) {
            $sql .= ' ON UPDATE ' . $update;
        }

        return $sql;
    }

    /**
     * Builds a SQL statement for dropping an index.
     * @param string $name the name of the index to be dropped. The name will be properly quoted by the method.
     * @param string $table the table whose index is to be dropped. The name will be properly quoted by the method.
     * @return string the SQL statement for dropping an index.
     */
    public function dropIndex($name, $table)
    {
        return 'DROP INDEX ' . $this->db->quoteTableName($name);
    }

    /**
     * Creates a SQL statement for resetting the sequence value of a table's primary key.
     * The sequence will be reset such that the primary key of the next new row inserted
     * will have the specified value or 1.
     * @param string $table the name of the table whose primary key sequence will be reset
     * @param array|string $value the value for the primary key of the next new row inserted. If this is not set,
     * the next new row's primary key will have a value 1.
     * @return string the SQL statement for resetting sequence
     * @throws NotSupportedException if this is not supported by the underlying DBMS
     */
    public function resetSequence($table, $value = null)
    {
        $tableSchema = $this->db->getTableSchema($table);
        if ($tableSchema === null) {
            throw new InvalidArgumentException("Unknown table: $table");
        }
        if ($tableSchema->sequenceName === null) {
            return '';
        }

        $sequence = $this->db->quoteTableName($tableSchema->sequenceName);
        $tableName = $this->db->quoteTableName($table);

        if ($value !== null) {
            $value = (int) $value;
        } else {
            // use master connection to get the biggest PK value
            $value = $this->db->useMaster(function (Connection $db) use ($sequence, $tableName) {
                return $db->createCommand("SELECT MAX({$sequence}) FROM {$tableName}")->queryScalar();
            }) + 1;
        }
        
        $serialType = $tableSchema->columns[$tableSchema->sequenceName]->dbType;
        
        return "ALTER TABLE {$tableName} MODIFY ({$sequence} $serialType ($value))";
    }

    /**
     * Builds the ORDER BY and LIMIT/OFFSET clauses and appends them to the given SQL.
     * @param string $sql the existing SQL (without ORDER BY/LIMIT/OFFSET)
     * @param array $orderBy the order by columns. See [[Query::orderBy]] for more details on how to specify this parameter.
     * @param integer $limit the limit number. See [[Query::limit]] for more details.
     * @param integer $offset the offset number. See [[Query::offset]] for more details.
     * @return string the SQL completed with ORDER BY/LIMIT/OFFSET (if any)
     */
    public function buildOrderByAndLimit($sql, $orderBy, $limit, $offset)
    {
        $orderBy = $this->buildOrderBy($orderBy);
        if ($orderBy !== '') {
            $sql .= $this->separator . $orderBy;
        }
        if ($this->hasLimit($limit)) {
            $find = '/^([\s(])*SELECT(\s+SKIP\s+\d+)?(\s+LIMIT\s+\d+)?(\s+DISTINCT)?/i';
            $replace = "\\1SELECT\\2 LIMIT $limit\\4";
            $sql = preg_replace($find, $replace, $sql);
        }
        if ($this->hasOffset($offset)) {
            $find = '/^([\s(])*SELECT(\s+SKIP\s+\d+)?(\s+DISTINCT)?/i';
            $replace =  "\\1SELECT SKIP $offset\\3";
            $sql = preg_replace($find, $replace, $sql);
        }
        return $sql;
    }


    /**
     * @param array $unions
     * @param array $params the binding parameters to be populated
     * @return string the UNION clause built from [[Query::$union]].
     * @throws Exception
     */
    public function buildUnion($unions, &$params)
    {
        if (empty($unions)) {
            return '';
        }

        $result = '';

        foreach ($unions as $i => $union) {
            $query = $union['query'];
            $prefix = '';
            if ($query instanceof Query) {
                if($query->limit || $query->offset){
                    $prefix = 'SELECT * FROM ';
                }
                list($unions[$i]['query'], $params) = $this->build($query, $params);
            }

            $result .= 'UNION ' . ($union['all'] ? 'ALL ' : '') . $prefix . '( ' . $unions[$i]['query'] . ' ) ';
        }

        return trim($result);
    }

    /**
     * {@inheritDoc}
     */
    public function selectExists($rawSql)
    {
        return 'SELECT CASE WHEN COUNT(*)>0 THEN 1 ELSE 0 END FROM (' . $rawSql . ') CHECKEXISTS';
    }
}
