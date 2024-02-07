CREATE TABLE llx_dolifleet_vehicule_link (
  rowid int(11) AUTO_INCREMENT PRIMARY KEY,
  date_creation datetime DEFAULT NULL,
  tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  date_start datetime DEFAULT NULL,
  date_end datetime DEFAULT NULL,
  fk_source int(11) DEFAULT 0,
  fk_target int(11) DEFAULT 0,
  fk_soc_vehicule_source int(11) NOT NULL DEFAULT 0,
  fk_soc_vehicule_target int(11) NOT NULL DEFAULT 0
) ENGINE=innodb;
