-- Tested with PostgreSQL 8.4.3 and 9.1.9
DROP TABLE seen;
DROP TABLE vendor;
DROP TABLE human;
DROP TABLE admin;
DROP TABLE species;
DROP TABLE corp;
DROP ROLE admin;
DROP ROLE human;
DROP ROLE vendor;
CREATE TABLE species (
	id SERIAL UNIQUE PRIMARY KEY,
	name text NOT NULL UNIQUE,
	short varchar(8)
);
CREATE TABLE human (
	id SERIAL UNIQUE PRIMARY KEY,
	fname text NOT NULL,
	lname text NOT NULL,
	email text NOT NULL,
	phone text,
	corp text,
	title text,
	address text,
	key varchar(32) UNIQUE,
	sid integer REFERENCES species(id),
	ext1 text,
	ext2 text,
	ext3 text,
	UNIQUE(fname, lname, email)
);
CREATE TABLE corp (
	id SERIAL UNIQUE PRIMARY KEY,
	name text NOT NULL UNIQUE
);
CREATE TABLE vendor (
	id SERIAL UNIQUE PRIMARY KEY,
	name text NOT NULL,
	email text NOT NULL,
	cid integer REFERENCES corp(id),
	key varchar(32) UNIQUE
);
CREATE TABLE seen (
	vid integer REFERENCES vendor(id),
	hid integer REFERENCES human(id),
	seen timestamp DEFAULT current_timestamp,
	sent boolean DEFAULT false,
	note text
);
CREATE TABLE admin (
	username varchar(20) PRIMARY KEY,
	pass varchar(123),
	lastlogin timestamp DEFAULT current_timestamp
);
CREATE USER admin;
GRANT select ON admin TO admin;
GRANT ALL ON human TO admin;
GRANT ALL ON vendor TO admin;
GRANT ALL ON species TO admin;
GRANT ALL ON corp TO admin;
GRANT ALL ON seen TO admin;
GRANT ALL ON human_id_seq TO admin;
GRANT ALL ON vendor_id_seq TO admin;
GRANT ALL ON corp_id_seq TO admin;
GRANT ALL ON species_id_seq TO admin;
CREATE USER human;
REVOKE ALL ON admin FROM human;
REVOKE ALL ON vendor FROM human;
REVOKE ALL ON corp FROM human;
GRANT select ON human TO human;
GRANT select ON species TO human;
GRANT insert ON seen TO human;
CREATE USER vendor;
REVOKE ALL ON admin FROM vendor;
REVOKE ALL ON species FROM vendor;
REVOKE ALL ON human FROM vendor;
REVOKE ALL ON seen FROM vendor;
REVOKE ALL ON corp FROM vendor;
GRANT select ON vendor TO vendor;
