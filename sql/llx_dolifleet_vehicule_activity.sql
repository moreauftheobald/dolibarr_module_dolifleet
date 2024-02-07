CREATE TABLE llx_dolifleet_vehicule_activity (
  rowid int(11) AUTO_INCREMENT PRIMARY KEY,
  date_creation datetime DEFAULT NULL,
  tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_vehicule int(11) DEFAULT 0,
  fk_type varchar(255) DEFAULT NULL,
  date_start datetime DEFAULT NULL,
  date_end datetime DEFAULT NULL,
  fk_soc int(11) NOT NULL DEFAULT 0
)ENGINE=innodb;
