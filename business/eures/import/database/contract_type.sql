CREATE TABLE contract_type
(
	id INT UNSIGNED NOT NULL AUTO_INCREMENT,
	name VARCHAR(255) UNIQUE,
	PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8;