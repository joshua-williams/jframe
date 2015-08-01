CREATE TABLE IF NOT EXISTS group_types(
	id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	group_type VARCHAR(45) UNIQUE,
	INDEX idx_gt(group_type)
);

INSERT INTO group_types (group_type) VALUES ('user'),('category'),('setting');

CREATE TABLE IF NOT EXISTS groups(
	group_id int(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	type_id int(11) UNSIGNED,
	rel_id int(11) UNSIGNED,
	parent_id int(11) UNSIGNED,
	title VARCHAR(255),
	disabled tinyint(1) DEFAULT 0,
	created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP()
);

CREATE TABLE table_map(
	map_id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	gt_id INT(11) UNSIGNED,
	src_id INT(11) UNSIGNED,
	rel_id INT(11) UNSIGNED,
	index idx_srid(src_id, rel_id),
	index idx_sid(src_id),
	index idx_rid(rel_id),
	index idx_gtid(gt_id)
);

CREATE TABLE IF NOT EXISTS sessions(
	session_id VARCHAR(45) PRIMARY KEY,
	user_id INT(11) UNSIGNED,
	token VARCHAR(45),
	token_created INT(11) UNSIGNED,
	last_activity INT(11) UNSIGNED,
	ip_address VARCHAR(255)
);

CREATE TABLE tokens(
	session_id varchar(45),
	token varchar(45),
	created int(11) unsigned,
	index idx_sid(session_id)
);
CREATE TABLE aliases(
	alias_id int(11) unsigned auto_increment primary key,
	title varchar(90),
	alias varchar(255)
);

CREATE TABLE settings(
	group_id int(11) unsigned,
	name VARCHAR(90),
	value VARCHAR(255),
	index idx_gid(group_id)
);