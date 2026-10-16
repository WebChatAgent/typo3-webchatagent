CREATE TABLE pages (
	tx_webchatagent_hide tinyint(1) unsigned DEFAULT '0' NOT NULL
);

CREATE TABLE tt_content (
	tx_webchatagent_chatbot_id varchar(36) DEFAULT '' NOT NULL,
	tx_webchatagent_height varchar(16) DEFAULT '' NOT NULL
);
