CREATE TABLE llx_dolifleet_vehicule_operation_np (
  rowid int(11) AUTO_INCREMENT PRIMARY KEY,
  date_creation datetime DEFAULT NULL,
  tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_vehicule int(11) NOT NULL DEFAULT 0,
  fk_product int(11) DEFAULT 0
) ENGINE=innodb;
