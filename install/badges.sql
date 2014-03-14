

CREATE TABLE badges_def (
	id mediumint(8) UNSIGNED AUTO_INCREMENT,
	name varchar(255) UNIQUE NOT NULL,
	description varchar(255),
	desc_uid varchar(8) DEFAULT '' NOT NULL,
	desc_bitfield varchar(255) DEFAULT '' NOT NULL,
	desc_options int(11) UNSIGNED DEFAULT '7' NOT NULL,
	url varchar(255) DEFAULT '' NOT NULL,
	PRIMARY KEY (id)
) CHARACTER SET `utf8` COLLATE `utf8_bin`;
	
CREATE TABLE titles_def (
	id mediumint(8) UNSIGNED AUTO_INCREMENT,
	title varchar(255) UNIQUE NOT NULL,
	description varchar(255),
	desc_uid varchar(8) DEFAULT '' NOT NULL,
	desc_bitfield varchar(255) DEFAULT '' NOT NULL,
	desc_options int(11) UNSIGNED DEFAULT '7' NOT NULL,
	PRIMARY KEY (id)
) CHARACTER SET `utf8` COLLATE `utf8_bin`;
	
CREATE TABLE badges_earned (
	user_id mediumint(8) UNSIGNED DEFAULT '0' NOT NULL,
	badge_id mediumint(8) UNSIGNED DEFAULT '0' NOT NULL,
	date_earned int(11) UNSIGNED DEFAULT '0' NOT NULL,
	display tinyint(1) UNSIGNED DEFAULT '0' NOT NULL,
	KEY (user_id),
	KEY (badge_id)
	) CHARACTER SET `utf8` COLLATE `utf8_bin`;
	
CREATE TABLE titles_earned (
	user_id mediumint(8) UNSIGNED DEFAULT '0' NOT NULL,
	title_id mediumint(8) UNSIGNED DEFAULT '0' NOT NULL,
	date_earned int(11) UNSIGNED DEFAULT '0' NOT NULL,
	display tinyint(1) UNSIGNED DEFAULT '0' NOT NULL,
	KEY (user_id),
	KEY (title_id)
) CHARACTER SET `utf8` COLLATE `utf8_bin`;
	
INSERT INTO badges_def (id,name,description,url) VALUES (1,'Test Badge','This badge is awarded for testing things','test.jpg');
INSERT INTO titles_def (id,title,description) VALUES (1,'Test Title','This title is granted for testing things');

