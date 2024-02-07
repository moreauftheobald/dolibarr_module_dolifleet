CREATE TABLE llx_dolifleet_rental_proposaldet (
  rowid int(11) AUTO_INCREMENT PRIMARY KEY,
  date_creation datetime DEFAULT NULL,
  tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_vehicule int(11) DEFAULT 0,
  fk_rental_proposal int(11) DEFAULT 0,
  total_ht double DEFAULT 0,
  description longtext DEFAULT NULL
)ENGINE=innodb;
