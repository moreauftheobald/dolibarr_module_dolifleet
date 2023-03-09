<?php

class cron_dolifleet
{

	private $db;

	/**
	 * Constructor
	 *
	 */
	public function __construct()
	{
		global $db;
		$this->db = $db;
	}


	public function getKmVehicles()
	{
		global $user, $conf;

		set_time_limit(0);

		dol_include_once('/dolifleet/class/vehicule.class.php');
		dol_include_once('/dolifleet/lib/dolifleet.lib.php');

		$this->langs = new Translate('', $conf);
		$this->langs->setDefaultLang('fr_FR');
		$this->langs->loadLangs(array('main', 'admin', 'cron', 'dict'));
		$this->langs->load('clitheobald@clitheobald');

		$this->errors = array();
		$this->output = '';
		$nbLinesProcessed = 0;

		$now = dol_now();
		$date = dol_print_date($now, "%d/%m/%Y %H:%M:%S");
		$this->output .= '<p>' . $date . ' Début de la tâche planifiée de ' . $this->langs->trans('2lTrucksCRONGetKmVehicles') . '</p>';

		//liste des véhicules sur le dolibarr

		$sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "c_dolifleet_vehicule_mark WHERE code = 'VOLVO'";
		$resql = $this->db->query($sql);

		if ($resql) {
			if ($this->db->num_rows($resql) == 0) {
				$this->errors[] = "Marque VOLVO introuvable";
			} else {
				$obj = $this->db->fetch_object($resql);
				$vehicle = new doliFleetVehicule($this->db);
				$TVehiclesDoli = $vehicle->fetchAll('', '', array('fk_vehicule_mark' => $obj->rowid,
					'status' => 1));
			}
		} else {
			$this->errors[] = $this->db->lasterror;
		}

		if (!empty($TVehiclesDoli) && empty($this->errors)) {

			//liste des véhicules dont on a accès depuis l'API
			$TVehiclesAPI = array();

			$moreDataAvailable = true;
			$data = array();
			$lastVin = 0;

			while ($moreDataAvailable == true) {
				if (!empty($lastVin)) $data = array('lastVin' => $lastVin);

				$rep = callAPI('GET', 'https://api.volvotrucks.com/vehicle/vehicles', $data, array('Accept: application/x.volvogroup.com.vehicles.v1.0+json; UTF-8'));

				if ($rep != -1) {
					foreach ($rep['vehicleResponse']['vehicles'] as $vehicle) {
						$TVehiclesAPI[] = $vehicle['vin'];
					}
				} else {
					$this->errors[] = "Connexion avec l'API impossible";
				}

				$moreDataAvailable = $rep['moreDataAvailable'];

				if ($moreDataAvailable) $lastVin = end($TVehiclesAPI);
			}

			foreach ($TVehiclesDoli as $vehicle) {
				//on traite seulement les véhicules disponibles dans l'API
				if (in_array($vehicle->vin, $TVehiclesAPI)) {
					$rep = callAPI('GET', 'https://api.volvotrucks.com/vehicle/vehiclestatuses', array('vin' => $vehicle->vin, 'latestOnly' => 'true', 'trigger' => 'DISTANCE_TRAVELLED'), array('Accept: application/x.volvogroup.com.vehiclestatuses.v1.0+json; UTF-8'));

					if ($rep != -1) {
						$km = $rep['vehicleStatusResponse']['vehicleStatuses'][0]['hrTotalVehicleDistance'] / 1000;
						$date_km = $rep['vehicleStatusResponse']['vehicleStatuses'][0]['receivedDateTime'];

						if (!empty($date_km)) {
							$TDateStr = explode('T', $date_km);
							$date_km = $TDateStr[0];
						}

						if (!empty($km)) {
							$this->db->begin();

							//Màj du nombre de kilomètres du véhicule
							$vehicle->km = $km;
							$vehicle->km_date = $date_km;
							$res = $vehicle->update($user);

							if ($res < 0) {
								$this->errors[] = 'Impossible de màj ce véhicule : ' . $vehicle->vin . " (fonction update())";
								$this->db->rollback();
							} else {
								$this->db->commit();

								$nbLinesProcessed++;
							}
						} else {
							$this->errors[] = "Impossible de màj ce véhicule : " . $vehicle->vin . " (aucune information de kilométrage provenant de l'API)";
						}
					} else {
						$this->errors[] = "Impossible de màj ce véhicule : " . $vehicle->vin;
					}
				} else {
					$this->errors[] = "Impossible de màj ce véhicule : " . $vehicle->vin . " (aucun accès API à ce véhicule)";
				}
			}

		} else {
			$this->errors[] = "Impossible de récupérer la liste des véhicules";
		}

		if (empty($this->errors)) {
			$comment = '<p>Traitement terminé avec succés (' . $nbLinesProcessed . ' véhicules traités)</p>';
		} else {

			$comment = '<p>Erreur lors du traitement. Les modifications sur les lignes en erreur n\'ont pas été appliquées. ' . count($this->errors) . ' lignes en erreur sur ' . $nbLinesProcessed . ' lignes traités</p>';

			foreach ($this->errors as $id => $errorMessage) {
				$output[] = $errorMessage;
			}

			$this->output .= '<ul><li>' . join('</li><li>', $output) . '</li></ul>';

			$this->sendResultByMail('Erreur : ' . $this->langs->trans('2lTrucksCRONGetKmVehicles'));
		}

		$now = dol_now();
		$date = dol_print_date($now, "%d/%m/%Y %H:%M:%S");
		$comment .= '<p>' . $date . ' Fin de la tâche planifiée de ' . $this->langs->trans('2lTrucksCRONGetKmVehicles') . '</p>';

		$this->output .= $comment;

		return empty($errors) ? 0 : 1;
	}

	/**
	 * @return int
	 *
	 */
	public function createEventOperationOrder()
	{
		global $conf, $user;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
		dol_include_once('dolifleet/class/vehiculeOperation.class.php');
		dol_include_once('operationorder/class/operationorderstatus.class.php');
		$operation = new dolifleetVehiculeOperation($this->db);

		$this->langs = new Translate('', $conf);
		$this->langs->setDefaultLang('fr_FR');
		$this->langs->loadLangs(array('main', 'admin', 'cron', 'dict'));
		$this->langs->load('clitheobald@clitheobald');

		$now = dol_now();
		$date = dol_print_date($now, "%d/%m/%Y %H:%M:%S");
		$this->output .= '<p>' . $date . ' Début de la tâche planifiée de ' . $this->langs->trans('2lTrucksCRONCreateEventOperationOrder') . '</p>';

		$TKmAvg = array();
		$TKmKMLast = array();
		$sql = "SELECT dv.rowid, dv.km/DATEDIFF(dv.km_date, dv.date_immat) as km_by_day_veh, dv.km
			FROM " . MAIN_DB_PREFIX . "dolifleet_vehicule as dv
			WHERE dv.date_immat IS NOT NULL AND dv.date_immat !='0000-00-00'
			AND dv.km_date IS NOT NULL AND dv.km_date !='0000-00-00'
			AND dv.km IS NOT NULL AND dv.km != 0
			AND dv.status=1";
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->output .= "Erreur SQL:" . $this->db->lasterror;
			return -1;
		}
		if (!empty($resql) && $this->db->num_rows($resql)) {
			while ($obj = $this->db->fetch_object($resql)) {
				$TKmAvg[$obj->rowid] = $obj->km_by_day_veh;
				$TKmKMLast[$obj->rowid] = $obj->km;
			}
		}

		$operationOrderStatus = new OperationOrderStatus($this->db);
		$resultStatus = $operationOrderStatus->fetchAll(0,0,array('display_on_planning'=>1));
		if (!is_array($resultStatus) && $resultStatus<0) {
			$this->output .= "Erreur Update:" . $operationOrderStatus->error . implode(',', $operationOrderStatus->errors);
			return $resultStatus;
		}

		$successCounter=0;

		$sql = "SELECT DISTINCT op.rowid as oprowid
       		FROM " . MAIN_DB_PREFIX . "dolifleet_vehicule_operation AS op
			INNER JOIN " . MAIN_DB_PREFIX . "dolifleet_vehicule AS vh ON vh.rowid = op.fk_vehicule WHERE vh.status = 1";

		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->output .= "Erreur SQL:" . $this->db->lasterror;
			return -1;
		}
		if (!empty($resql) && $this->db->num_rows($resql)) {
			while ($obj = $this->db->fetch_object($resql)) {
				$resultFetch = $operation->fetch($obj->oprowid);
				if ($resultFetch < 0) {
					$this->output .= "Erreur Fetch:" . $operation->error . implode(',', $operation->errors);
					return $resultFetch;
				}

				if(!empty($operation->delai_from_last_op) && $operation->delai_from_last_op > 0){
					$operation->date_next = dol_time_plus_duree($operation->date_done, (int)$operation->delai_from_last_op, 'm');
				}

				if (!empty($operation->km)) {
					if (empty($operation->km_next)) {
						$operation->km_next =$operation->km_done+$operation->km;
					}
					$diffKm = $operation->km_next - $TKmKMLast[$operation->fk_vehicule];

					if ($diffKm > 0) {
						$nbDays=0;
						if (array_key_exists($operation->fk_vehicule, $TKmAvg) && !empty($TKmAvg[$operation->fk_vehicule])) {
							$nbDays = $diffKm / $TKmAvg[$operation->fk_vehicule];
						}
						$dt = dol_time_plus_duree(dol_now(), (int)$nbDays, 'd');

						if ($dt<$operation->date_next) {
							$operation->date_next = $dt;
						}
					} else {
						$operation->date_next = dol_now();
					}
				}


				$stToTest=array();
				$operation->or_next=null;
				if (!empty($resultStatus)) {
					foreach($resultStatus as $dStatus) {
						$stToTest[]=$dStatus->id;
					}

					$sql = "SELECT ordp.rowid";
					$sql .= " FROM ".MAIN_DB_PREFIX."operationorder as ordp INNER JOIN ".MAIN_DB_PREFIX."operationorderdet as ord";
					$sql .= " ON ordp.rowid=ord.fk_operation_order";
					$sql .= " WHERE ordp.fk_vehicule=".(int)$operation->fk_vehicule." AND ord.fk_product=".(int)$operation->fk_product;
					$sql .= " AND ordp.status IN (".implode(',',$stToTest).")";
					$sql .= " AND ordp.planned_date >= '".$this->db->idate($operation->date_done)."'";
					$sql .= " ORDER BY planned_date";
					$sql .= " LIMIT 1";


					$resqlOR = $this->db->query($sql);
					if (!$resqlOR) {
						$this->output .= "Erreur SQL:" . $this->db->lasterror;
						return -1;
					}
					if ($objOR=$this->db->fetch_object($resqlOR)) {
						if (!empty($objOR->rowid)) {
							$operation->or_next = $objOR->rowid;
						}
					}
				}

				$resultUpd = $operation->update($user);
				if ($resultUpd < 0) {
					$this->output .= "Erreur Update:" . $operation->error . implode(',', $operation->errors);
					return $resultUpd;
				}

				$successCounter++;
			}
		}


		if (!empty($successCounter)) $this->output .= $this->langs->trans('Sucessfully').$successCounter;

		$now = dol_now();
		$date = dol_print_date($now, "%d/%m/%Y %H:%M:%S");
		$this->output .= '<p>' . $date . ' Fin de la tâche planifiée de ' . $this->langs->trans('2lTrucksCRONCreateEventOperationOrder') . '</p>';

		if (!empty($error)) $this->sendResultByMail($this->langs->transnoentities('createEventOperationOrderError'));

		return empty($error) ? 0 : 1;

	}

	/**
	 * @param string $subject
	 */
	private function sendResultByMail($subject = '')
	{
		global $conf, $user, $db, $langs;
		if (!empty($conf->global->MAIN_MAIL_ERRORS_TO) && !empty($conf->global->MAIN_MAIL_EMAIL_FROM)) {
			require_once DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php';
			$sendto = $conf->global->MAIN_MAIL_ERRORS_TO;
			$from = dol_string_nospecial($conf->global->MAIN_MAIL_EMAIL_FROM, ' ', array(",")) . ' <' . $conf->global->MAIN_MAIL_EMAIL_FROM . '>';
			$message = $this->output;
			$mailfile = new CMailFile($subject, $sendto, $from, $message, array(), array(), array(), '', '', 0, 1, '', '', '', '', 'standard');
			if ($mailfile->error) {
				$this->output .= '<p style="color:red;font-weight: bold"> Probléme d\'envoie du mail de compte rendue</p>';
			} else {
				$sql = 'SELECT rowid FROM ' . MAIN_DB_PREFIX . 'user WHERE email=\'' . $conf->global->MAIN_MAIL_EMAIL_FROM . '\'';
				$resql = $db->query($sql);
				if ($resql <= 0) {
					$this->output .= '<p>' . $langs->trans('SQLERROR', $db->lastqueryerror()) . '</p>';
				}
				if ($obj = $db->fetch_object($resql)) {
					$currentuser = $user->id;
					if (!empty($obj->rowid)) {
						$user->fetch($obj->rowid);
						$result = $mailfile->sendfile();
						if ($result < 0) {
							$this->output .= '<p style="color:red;font-weight: bold"> Probléme d\'envoi du mail de compte rendue</p>';
						}
						$user->fetch($currentuser);
					} else {
						$this->output .= '<p style="color:red;font-weight: bold">Impossible de trouver un utilisateur pour envoyer les mails</p>';
					}
				} else {
					$this->output .= '<p style="color:red;font-weight: bold">Impossible de trouver un utilisateur pour envoyer les mails</p>';
				}
			}
		}
	}

}
