CREATE TABLE llx_dolifleet_vehicule_rental_matrix (
  rowid int(11) AUTO_INCREMENT PRIMARY KEY,
  date_creation datetime DEFAULT NULL,
  tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_soc int(11) NOT NULL DEFAULT 0,
  fk_c_type_vh varchar(255) DEFAULT NULL,
  fk_c_mark_vh varchar(255) DEFAULT NULL,
  delay int(11) DEFAULT 0,
  amount_ht double DEFAULT 0
)ENGINE=innodb;

