CREATE TABLE llx_dolifleet_vehicule_operation (
  rowid int(11) AUTO_INCREMENT PRIMARY KEY,
  date_creation datetime DEFAULT NULL,
  tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_vehicule int(11) DEFAULT 0,
  fk_product int(11) DEFAULT 0,
  status int(11) NOT NULL DEFAULT 0,
  rang int(11) DEFAULT 0,
  km double DEFAULT 0,
  delai_from_last_op int(11) DEFAULT 0,
  date_done datetime DEFAULT NULL,
  km_done double DEFAULT 0,
  date_next datetime DEFAULT NULL,
  km_next double DEFAULT 0,
  on_time int(11) DEFAULT 0,
  or_next int(11) DEFAULT 0,
  date_due datetime DEFAULT NULL
)ENGINE=innodb;
