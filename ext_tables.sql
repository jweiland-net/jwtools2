#
# Table structure for table 'tx_jwtools2_stored_routes'
#
CREATE TABLE tx_jwtools2_stored_routes (
	root_page int(11) DEFAULT '0' NOT NULL,
	tablename varchar(60) DEFAULT '' NOT NULL,
	fieldname varchar(60) DEFAULT '' NOT NULL,
	identifier varchar(20) DEFAULT '' NOT NULL,
	source varchar(255) DEFAULT '' NOT NULL,
	target varchar(255) DEFAULT '' NOT NULL,

	KEY source_path (source(50), root_page, tablename(20), fieldname(20)),
	KEY target_path (target(50), root_page, tablename(20), fieldname(20))
);

#
# Table structure for table 'tx_jwtools2_cache_expression'
#
CREATE TABLE tx_jwtools2_cache_expression (
	title varchar(100) DEFAULT '' NOT NULL,
	is_regexp tinyint(1) DEFAULT '0' NOT NULL,
	is_exception tinyint(1) DEFAULT '0' NOT NULL,
	exception_fe_only tinyint(1) DEFAULT '1' NOT NULL,
	expression varchar(255) DEFAULT '' NOT NULL
);
