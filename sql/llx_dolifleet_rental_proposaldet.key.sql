ALTER TABLE llx_dolifleet_rental_proposaldet ADD UNIQUE INDEX uk_dolifleet_rental_proposal_fk_vehicule (fk_vehicule, fk_rental_proposal);

ALTER TABLE llx_dolifleet_rental_proposaldet ADD CONSTRAINT fk_dolifleet_rental_proposal FOREIGN KEY (fk_rental_proposal) REFERENCES llx_dolifleet_rental_proposal (rowid);
ALTER TABLE llx_dolifleet_rental_proposaldet ADD CONSTRAINT fk_dolifleet_vehicule FOREIGN KEY (fk_vehicule) REFERENCES llx_dolifleet_vehicule (rowid);

