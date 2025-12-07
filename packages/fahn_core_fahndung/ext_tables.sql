--
-- Table structure for table 'tx_fahncorefahndung_domain_model_fahndung'
--
CREATE TABLE tx_fahncorefahndung_domain_model_fahndung (
    uid int(11) NOT NULL auto_increment,
    pid int(11) DEFAULT '0' NOT NULL,

    -- Pflichtfelder
    title varchar(255) DEFAULT '' NOT NULL,
    description text,
    case_id varchar(100) DEFAULT '' NOT NULL,

    -- Relationen
    categories int(11) unsigned DEFAULT '0' NOT NULL,
    images int(11) unsigned DEFAULT '0' NOT NULL,

    -- Optionale Felder
    date_of_crime int(11) unsigned DEFAULT '0',
    location varchar(255) DEFAULT '',

    -- Status
    is_published tinyint(1) unsigned DEFAULT '0' NOT NULL,

    -- TYPO3 Standard-Felder (versioningWS)
    t3ver_oid int(11) DEFAULT '0' NOT NULL,
    t3ver_wsid int(11) DEFAULT '0' NOT NULL,
    t3ver_state tinyint(4) DEFAULT '0' NOT NULL,
    t3ver_stage int(11) DEFAULT '0' NOT NULL,

    -- Enable Fields
    hidden tinyint(4) unsigned DEFAULT '0' NOT NULL,
    deleted tinyint(4) unsigned DEFAULT '0' NOT NULL,
    starttime int(11) unsigned DEFAULT '0' NOT NULL,
    endtime int(11) unsigned DEFAULT '0' NOT NULL,

    -- Zeitstempel
    tstamp int(11) unsigned DEFAULT '0' NOT NULL,
    crdate int(11) unsigned DEFAULT '0' NOT NULL,
    cruser_id int(11) unsigned DEFAULT '0' NOT NULL,

    sorting int(11) DEFAULT '0' NOT NULL,

    PRIMARY KEY (uid),
    KEY parent (pid),
    KEY t3ver_oid (t3ver_oid, t3ver_wsid)
);

--
-- Table structure for sys_category MM relation
--
CREATE TABLE tx_fahncorefahndung_fahndung_category_mm (
    uid_local int(11) unsigned DEFAULT '0' NOT NULL,
    uid_foreign int(11) unsigned DEFAULT '0' NOT NULL,
    sorting int(11) unsigned DEFAULT '0' NOT NULL,
    sorting_foreign int(11) unsigned DEFAULT '0' NOT NULL,

    KEY uid_local (uid_local),
    KEY uid_foreign (uid_foreign)
);









