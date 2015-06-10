CREATE TABLE people(
	id SERIAL PRIMARY KEY,
	first_name VARCHAR NOT NULL,
	last_name VARCHAR,
	date_of_birth DATE,
	phone_number VARCHAR,
	email_address VARCHAR,
	employed BOOLEAN,
	iq integer
);


CREATE TABLE friends(
	person INTEGER NOT NULL REFERENCES people(id),
	friend INTEGER NOT NULL REFERENCES people(id),
	PRIMARY KEY(person, friend)
);
