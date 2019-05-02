BEGIN WORK;
    DROP TABLE composite_fk CASCADE;
    DROP TABLE order_item CASCADE;
    DROP TABLE item CASCADE;
    DROP TABLE order_item_with_null_fk CASCADE;
    DROP TABLE order CASCADE;
    DROP TABLE order_with_null_fk CASCADE;
    DROP TABLE category CASCADE;
    DROP TABLE customer CASCADE;
    DROP TABLE profile CASCADE;
    DROP TABLE type CASCADE;
    DROP TABLE null_values CASCADE;
    DROP TABLE constraints CASCADE;
    DROP TABLE bool_values CASCADE;
    DROP TABLE animal CASCADE;
    DROP TABLE DEFAULT_pk CASCADE;
    DROP TABLE document CASCADE;
    DROP TABLE comment CASCADE;
    DROP TABLE department CASCADE;
    DROP TABLE employee CASCADE;
    DROP TABLE dossier CASCADE;
    DROP TABLE validator_main CASCADE;
    DROP TABLE validator_ref CASCADE;
    DROP TABLE bit_values CASCADE;
    DROP TABLE t_constraints_1 CASCADE;
    DROP TABLE t_constraints_2 CASCADE;
    DROP TABLE t_constraints_3 CASCADE;
    DROP TABLE t_constraints_4 CASCADE;
    DROP VIEW animal_view;
COMMIT WORK;--

-- TABLES

CREATE TABLE constraints
(
  id INTEGER NOT NULL,
  field1 VARCHAR(255)
);

CREATE TABLE profile (
  id serial NOT NULL PRIMARY KEY,
  description VARCHAR(128) NOT NULL
);

CREATE TABLE customer (
  id serial NOT NULL PRIMARY KEY,
  email VARCHAR(128) NOT NULL,
  name VARCHAR(128),
  address text,
  status INTEGER DEFAULT 0,
  bool_status boolean DEFAULT 'f',
  profile_id INTEGER
);

CREATE TABLE category (
  id serial NOT NULL PRIMARY KEY,
  name VARCHAR(128) NOT NULL
);

CREATE TABLE item (
  id serial NOT NULL PRIMARY KEY,
  name VARCHAR(128) NOT NULL,
  category_id INTEGER NOT NULL references category(id) on DELETE CASCADE
);

CREATE TABLE order (
  id serial NOT NULL PRIMARY KEY,
  customer_id INTEGER NOT NULL references customer(id) on DELETE CASCADE,
  created_at INTEGER NOT NULL,
  total decimal(10,0) NOT NULL
);

CREATE TABLE order_with_null_fk (
  id serial NOT NULL PRIMARY KEY,
  customer_id INTEGER,
  created_at INTEGER NOT NULL,
  total decimal(10,0) NOT NULL
);

CREATE TABLE order_item (
  order_id INTEGER NOT NULL references order(id) on DELETE CASCADE,
  item_id INTEGER NOT NULL references item(id) on DELETE CASCADE,
  quantity INTEGER NOT NULL,
  subtotal decimal(10,0) NOT NULL,
  PRIMARY KEY (order_id,item_id)
);

CREATE TABLE order_item_with_null_fk (
  order_id INTEGER,
  item_id INTEGER,
  quantity INTEGER NOT NULL,
  subtotal decimal(10,0) NOT NULL
);

CREATE TABLE composite_fk (
  id INTEGER NOT NULL,
  order_id INTEGER NOT NULL,
  item_id INTEGER NOT NULL,
  PRIMARY KEY (id),
  FOREIGN KEY (order_id, item_id) REFERENCES order_item (order_id, item_id) ON DELETE CASCADE CONSTRAINT FK_composite_fk_order_item
);

CREATE TABLE null_values (
  id serial NOT NULL,
  var1 INTEGER,
  var2 INTEGER,
  var3 INTEGER DEFAULT NULL,
  stringcol VARCHAR(32) DEFAULT NULL,
  PRIMARY KEY (id)
);

CREATE TABLE type (
  int_col INTEGER NOT NULL,
  int_col2 INTEGER DEFAULT 1,
  smallint_col smallint DEFAULT 1,
  char_col char(100) NOT NULL,
  char_col2 VARCHAR(100) DEFAULT 'something',
  char_col3 text,
  float_col double precision NOT NULL,
  float_col2 double precision DEFAULT 1.23,
  blob_col clob,
  numeric_col decimal(5,2) DEFAULT 33.22,
  time DATETIME YEAR TO SECOND DEFAULT DATETIME(2002-01-01 00:00:00) YEAR TO SECOND NOT NULL,
  bool_col boolean NOT NULL,
  bool_col2 boolean DEFAULT 't',
  bool_col3 boolean DEFAULT 'f',
  ts_DEFAULT DATETIME YEAR TO SECOND DEFAULT CURRENT YEAR TO SECOND NOT NULL,
  bit_col SMALLINT DEFAULT 130 NOT NULL
);

CREATE TABLE bool_values (
  id serial NOT NULL PRIMARY KEY,
  bool_col boolean,
  DEFAULT_true boolean DEFAULT 't' NOT NULL,
  DEFAULT_false boolean  DEFAULT 'f' NOT NULL
);

CREATE TABLE animal (
  id serial PRIMARY KEY,
  type VARCHAR(255) NOT NULL
);

CREATE TABLE default_pk (
  id INTEGER DEFAULT 5 NOT NULL PRIMARY KEY,
  type VARCHAR(255) NOT NULL
);

CREATE TABLE bit_values (
  id serial NOT NULL PRIMARY KEY,
  val smallint NOT NULL
);

CREATE TABLE document (
  id serial PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  content text NOT NULL,
  version INTEGER DEFAULT 0 NOT NULL
);

CREATE TABLE comment (
  id serial PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  message clob NOT NULL
);

CREATE TABLE department (
  id serial PRIMARY KEY,
  title VARCHAR(255) NOT NULL
);

CREATE TABLE employee (
  id INTEGER NOT NULL,
  department_id INTEGER NOT NULL,
  first_name VARCHAR(255) NOT NULL,
  last_name VARCHAR(255) NOT NULL,
  PRIMARY KEY (id, department_id)
);

CREATE TABLE dossier (
  id serial PRIMARY KEY,
  department_id INTEGER NOT NULL,
  employee_id INTEGER NOT NULL,
  summary VARCHAR(255) NOT NULL
);

CREATE TABLE validator_main (
  id INTEGER NOT NULL PRIMARY KEY,
  field1 VARCHAR(255)
);

CREATE TABLE validator_ref (
  id INTEGER NOT NULL PRIMARY KEY,
  a_field VARCHAR(255),
  ref     INTEGER
);

CREATE TABLE T_constraints_1
(
    C_id integer NOT NULL PRIMARY KEY,
    C_not_null integer NOT NULL,
    C_check VARCHAR(255) NULL CHECK (C_check <> ''),
    C_unique integer NOT NULL,
    C_default integer NOT NULL DEFAULT 0,
    UNIQUE (C_unique) CONSTRAINT CN_unique
);

CREATE TABLE T_constraints_2
(
    C_id_1 integer NOT NULL,
    C_id_2 integer NOT NULL,
    C_index_1 integer NULL,
    C_index_2_1 integer NOT NULL DEFAULT 0,
    C_index_2_2 integer NOT NULL DEFAULT 0,
    UNIQUE (C_index_2_1, C_index_2_2) CONSTRAINT CN_constraints_2_multi,
    PRIMARY KEY (C_id_1, C_id_2) CONSTRAINT CN_pk
);

CREATE INDEX CN_constraints_2_single ON T_constraints_2 (C_index_1);

CREATE TABLE T_constraints_3
(
    C_id integer NOT NULL,
    C_fk_id_1 integer NOT NULL,
    C_fk_id_2 integer NOT NULL,
    FOREIGN KEY (C_fk_id_1, C_fk_id_2) REFERENCES T_constraints_2 (C_id_1, C_id_2) ON DELETE CASCADE CONSTRAINT CN_constraints_3
);

CREATE TABLE T_constraints_4
(
    C_id integer NOT NULL PRIMARY KEY,
    C_col_1 integer NOT NULL DEFAULT 0,
    C_col_2 integer NOT NULL DEFAULT 0,
    UNIQUE (C_col_1, C_col_2) CONSTRAINT CN_constraints_4
);

-- VIEWS
CREATE VIEW animal_view AS SELECT * FROM animal;

-- DATA
INSERT INTO animal (type) VALUES ('yiiunit\data\ar\Cat');
INSERT INTO animal (type) VALUES ('yiiunit\data\ar\Dog');

INSERT INTO profile (description) VALUES ('profile customer 1');
INSERT INTO profile (description) VALUES ('profile customer 3');

INSERT INTO customer (email, name, address, status, bool_status, profile_id) VALUES ('user1@example.com', 'user1', 'address1', 1, 't', 1);
INSERT INTO customer (email, name, address, status, bool_status) VALUES ('user2@example.com', 'user2', 'address2', 1, 't');
INSERT INTO customer (email, name, address, status, bool_status, profile_id) VALUES ('user3@example.com', 'user3', 'address3', 2, 'f', 2);

INSERT INTO category (name) VALUES ('Books');
INSERT INTO category (name) VALUES ('Movies');

INSERT INTO item (name, category_id) VALUES ('Agile Web Application Development with Yii1.1 and PHP5', 1);
INSERT INTO item (name, category_id) VALUES ('Yii 1.1 Application Development Cookbook', 1);
INSERT INTO item (name, category_id) VALUES ('Ice Age', 2);
INSERT INTO item (name, category_id) VALUES ('Toy Story', 2);
INSERT INTO item (name, category_id) VALUES ('Cars', 2);

INSERT INTO order (customer_id, created_at, total) VALUES (1, 1325282384, 110.0);
INSERT INTO order (customer_id, created_at, total) VALUES (2, 1325334482, 33.0);
INSERT INTO order (customer_id, created_at, total) VALUES (2, 1325502201, 40.0);

INSERT INTO order_with_null_fk (customer_id, created_at, total) VALUES (1, 1325282384, 110.0);
INSERT INTO order_with_null_fk (customer_id, created_at, total) VALUES (2, 1325334482, 33.0);
INSERT INTO order_with_null_fk (customer_id, created_at, total) VALUES (2, 1325502201, 40.0);

INSERT INTO order_item (order_id, item_id, quantity, subtotal) VALUES (1, 1, 1, 30.0);
INSERT INTO order_item (order_id, item_id, quantity, subtotal) VALUES (1, 2, 2, 40.0);
INSERT INTO order_item (order_id, item_id, quantity, subtotal) VALUES (2, 4, 1, 10.0);
INSERT INTO order_item (order_id, item_id, quantity, subtotal) VALUES (2, 5, 1, 15.0);
INSERT INTO order_item (order_id, item_id, quantity, subtotal) VALUES (2, 3, 1, 8.0);
INSERT INTO order_item (order_id, item_id, quantity, subtotal) VALUES (3, 2, 1, 40.0);

INSERT INTO order_item_with_null_fk (order_id, item_id, quantity, subtotal) VALUES (1, 1, 1, 30.0);
INSERT INTO order_item_with_null_fk (order_id, item_id, quantity, subtotal) VALUES (1, 2, 2, 40.0);
INSERT INTO order_item_with_null_fk (order_id, item_id, quantity, subtotal) VALUES (2, 4, 1, 10.0);
INSERT INTO order_item_with_null_fk (order_id, item_id, quantity, subtotal) VALUES (2, 5, 1, 15.0);
INSERT INTO order_item_with_null_fk (order_id, item_id, quantity, subtotal) VALUES (2, 3, 1, 8.0);
INSERT INTO order_item_with_null_fk (order_id, item_id, quantity, subtotal) VALUES (3, 2, 1, 40.0);

INSERT INTO document (title, content, version) VALUES ('Yii 2.0 guide', 'This is Yii 2.0 guide', 0);

INSERT INTO department (id, title) VALUES (1, 'IT');
INSERT INTO department (id, title) VALUES (2, 'accounting');

INSERT INTO employee (id, department_id, first_name, last_name) VALUES (1, 1, 'John', 'Doe');
INSERT INTO employee (id, department_id, first_name, last_name) VALUES (1, 2, 'Ann', 'Smith');
INSERT INTO employee (id, department_id, first_name, last_name) VALUES (2, 2, 'Will', 'Smith');

INSERT INTO dossier (id, department_id, employee_id, summary) VALUES (1, 1, 1, 'Excellent employee.');
INSERT INTO dossier (id, department_id, employee_id, summary) VALUES (2, 2, 1, 'Brilliant employee.');
INSERT INTO dossier (id, department_id, employee_id, summary) VALUES (3, 2, 2, 'Good employee.');

INSERT INTO validator_main (id, field1) VALUES (1, 'just a string1');
INSERT INTO validator_main (id, field1) VALUES (2, 'just a string2');
INSERT INTO validator_main (id, field1) VALUES (3, 'just a string3');
INSERT INTO validator_main (id, field1) VALUES (4, 'just a string4');
INSERT INTO validator_ref (id, a_field, ref) VALUES (1, 'ref_to_2', 2);
INSERT INTO validator_ref (id, a_field, ref) VALUES (2, 'ref_to_2', 2);
INSERT INTO validator_ref (id, a_field, ref) VALUES (3, 'ref_to_3', 3);
INSERT INTO validator_ref (id, a_field, ref) VALUES (4, 'ref_to_4', 4);
INSERT INTO validator_ref (id, a_field, ref) VALUES (5, 'ref_to_4', 4);
INSERT INTO validator_ref (id, a_field, ref) VALUES (6, 'ref_to_5', 5);

INSERT INTO bit_values (id, val) VALUES (1, 0);
INSERT INTO bit_values (id, val) VALUES (2, 1);