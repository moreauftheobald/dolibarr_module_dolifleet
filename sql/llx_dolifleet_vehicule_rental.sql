CREATE TABLE llx_dolifleet_vehicule_rental (
  rowid int(11) AUTO_INCREMENT PRIMARY KEY,
  date_creation datetime DEFAULT NULL,
  tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_vehicule int(11) DEFAULT 0,
  date_start datetime DEFAULT NULL,
  date_end datetime DEFAULT NULL,
  total_ht double DEFAULT 0,
  fk_soc int(11) DEFAULT 0,
  fk_proposaldet int(11) DEFAULT 0
) ENGINE=innodb;
