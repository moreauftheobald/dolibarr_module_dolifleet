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
				$TVehiclesDoli = $vehicle->fetchAll('', '', array('fk_vehicule_mark' => $obj->rowid, 'status' => 1));
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

				$rep = $this->callAPI('GET', 'https://api.volvotrucks.com/vehicle/vehicles', $data, array('Accept: application/x.volvogroup.com.vehicles.v1.0+json; UTF-8'));

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
					$rep = $this->callAPI('GET', 'https://api.volvotrucks.com/vehicle/vehiclestatuses', array('vin' => $vehicle->vin, 'latestOnly' => 'true', 'trigger' => 'DISTANCE_TRAVELLED'), array('Accept: application/x.volvogroup.com.vehiclestatuses.v1.0+json; UTF-8'));

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
		global $conf;

		$this->langs = new Translate('', $conf);
		$this->langs->setDefaultLang('fr_FR');
		$this->langs->loadLangs(array('main', 'admin', 'cron', 'dict'));
		$this->langs->load('clitheobald@clitheobald');

		$now = dol_now();
		$date = dol_print_date($now, "%d/%m/%Y %H:%M:%S");
		$this->output .= '<p>' . $date . ' Début de la tâche planifiée de ' . $this->langs->trans('2lTrucksCRONCreateEventOperationOrder') . '</p>';




		if (!empty($successCounter)) $this->output .= $this->langs->trans('EventCreatedSucessfully', $successCounter);

		$now = dol_now();
		$date = dol_print_date($now, "%d/%m/%Y %H:%M:%S");
		$this->output .= '<p>' . $date . ' Fin de la tâche planifiée de ' . $this->langs->trans('2lTrucksCRONCreateEventOperationOrder') . '</p>';

		if (!empty($error)) $this->sendResultByMail($this->langs->transnoentities('createEventOperationOrderError'));

		return empty($error) ? 0 : 1;

	}


	/**
	 * @param       $method
	 * @param       $url
	 * @param false $data
	 * @param false $header
	 * @return array|false|int|mixed|object
	 */
	function CallAPI($method, $url, $data = false, $header = false)
	{
		global $conf;

		$curl = curl_init();

		switch ($method) {
			case "POST":
				curl_setopt($curl, CURLOPT_POST, 1);
				if ($data) curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
				break;

			case "PUT":
				curl_setopt($curl, CURLOPT_PUT, 1);
				break;

			default:
				if ($data) $url = sprintf("%s?%s", $url, http_build_query($data));
		}
		curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($curl, CURLOPT_USERPWD, $conf->global->THEO_API_USER . ':' . $conf->global->THEO_API_PASS);

		curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
		curl_setopt($curl, CURLOPT_HEADER, false);

		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

		$result = curl_exec($curl);
		sleep(1);
		if (curl_getinfo($curl, CURLINFO_HTTP_CODE) == 200) {
			$TVehicleStatus = json_decode($result, true);
			curl_close($curl);
			return $TVehicleStatus;
		} else {
			//var_dump(curl_getinfo($curl));
			curl_close($curl);
			//exit;
			return -1;
		}
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
