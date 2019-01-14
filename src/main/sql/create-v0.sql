-- Creating tables
create table configuration (
  name text primary key not null,
  value text not null
);

create table user (
  name text primary key not null,
  password text not null
);

create table album (
  name text primary key not null,
  title text not null,
  created datetime not null
);

-- Inserting default configuration
insert into configuration (name, value) values ("theme", "default");
insert into configuration (name, value) values ("title", "Dialog");

-- Your initial admin password is $PASS (without the quotes)
insert into user (name, password) values ("admin", $HASH);