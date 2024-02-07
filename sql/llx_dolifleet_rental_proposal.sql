CREATE TABLE llx_dolifleet_rental_proposal (
  rowid int(11) AUTO_INCREMENT PRIMARY KEY,
  date_creation datetime DEFAULT NULL,
  tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  month int(11) DEFAULT 0,
  year int(11) DEFAULT 0,
  fk_soc int(11) NOT NULL DEFAULT 0,
  status int(11) NOT NULL DEFAULT 0,
  fk_first_valid int(11) DEFAULT 0,
  date_first_valid datetime DEFAULT NULL,
  fk_second_valid int(11) DEFAULT 0,
  date_second_valid datetime DEFAULT NULL,
  ref varchar(50) DEFAULT NULL,
  entity int(11) NOT NULL DEFAULT 1,
  model_pdf varchar(255) DEFAULT NULL
)ENGINE=innodb;
