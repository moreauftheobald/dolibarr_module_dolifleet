ALTER TABLE llx_dolifleet_vehicule_rental ADD CONSTRAINT fk_dolifleet_vehicule_rental_vehicule FOREIGN KEY (fk_vehicule) REFERENCES llx_dolifleet_vehicule (rowid);
ALTER TABLE llx_dolifleet_vehicule_rental ADD CONSTRAINT fk_dolifleet_vehicule_rental_fk_soc FOREIGN KEY (fk_soc) REFERENCES llx_societe (rowid);

