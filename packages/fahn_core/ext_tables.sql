--
-- A.7.1.3: Accessibility-Erweiterung für sys_file_reference
-- Spalte für dekorative Bilder
--
CREATE TABLE sys_file_reference (
    tx_is_decorative tinyint(1) unsigned DEFAULT '0' NOT NULL
);

