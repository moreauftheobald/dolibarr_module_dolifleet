CREATE TABLE llx_dolifleet_vehicule_extrafields (
  rowid int(11) AUTO_INCREMENT PRIMARY KEY,
  tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_object int(11) DEFAULT NULL,
  import_key varchar(14) DEFAULT NULL
) ENGINE=innodb;
