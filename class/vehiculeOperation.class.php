<?php
/* Copyright (C) 2020 ATM Consulting <support@atm-consulting.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

if (!class_exists('SeedObject')) {
	/**
	 * Needed if $form->showLinkedObjectBlock() is call or for session timeout on our module page
	 */
	define('INC_FROM_DOLIBARR', true);
	require_once dirname(__FILE__) . '/../config.php';
}

class dolifleetVehiculeOperation extends SeedObject
{
	/**
	 * Draft status
	 */
	const STATUS_DRAFT = 0;

	/**
	 * To Plan status
	 */
	const STATUS_TOPLAN = 1;

	/**
	 * Planned status
	 */
	const STATUS_PLANNED = 2;

	/**
	 * Done status
	 */
	const STATUS_DONE = 3;

	/** @var array $TStatus Array of translate key for each const */
	public static $TStatus = array(
		self::STATUS_DRAFT => 'doliFleetOperationStatusShortDraft'
	, self::STATUS_TOPLAN => 'doliFleetOperationStatusShortToPlan'
	, self::STATUS_PLANNED => 'doliFleetOperationStatusShortPlanned'
	, self::STATUS_DONE => 'doliFleetOperationStatusShortDone'
	);

	/** @var string $table_element Table name in SQL */
	public $table_element = 'dolifleet_vehicule_operation';

	/** @var string $element Name of the element (tip for better integration in Dolibarr: this value should be the reflection of the class name with ucfirst() function) */
	public $element = 'dolifleet_vehicule_operation';

	/** @var int $fk_vehicule Object link to vehicule */
	public $fk_vehicule;

	public $fk_product;

	public $status;

	public $km;

	public $delay_from_last_op;

	public $date_done;

	public $rang;

	public $km_done;

	public $date_next;
	public $km_next;
	public $on_time;
	public $or_next;

	public $fields = array(
		'fk_vehicule' => array(
			'type' => 'integer:doliFleetVehicule:dolifleet/class/vehicule.class.php',
			'label' => 'doliFleetVehicule',
			'visible' => 1,
			'enabled' => 1,
			'position' => 10,
			'index' => 1,
		),

		'fk_product' => array(
			'type' => 'integer:Product:product/class/product.class.php',
			'label' => 'VehiculeOperation',
			'visible' => 1,
			'enabled' => 1,
			'position' => 20,
			'index' => 1,
		),

		'status' => array(
			'type' => 'integer',
			'label' => 'Status',
			'enabled' => 1,
			'visible' => 0,
			'notnull' => 1,
			'default' => 1,
			'index' => 1,
			'position' => 30,
			'arrayofkeyval' => array(
				self::STATUS_DRAFT => 'doliFleetOperationStatusShortDraft'
			, self::STATUS_TOPLAN => 'doliFleetOperationStatusShortToPlan'
			, self::STATUS_PLANNED => 'doliFleetOperationStatusShortPlanned'
			, self::STATUS_DONE => 'doliFleetOperationStatusShortDone'
			)
		),

		'rang' => array(
			'type' => 'integer',
			'visible' => 0,
			'enabled' => 1,
			'position' => 40
		),

		'km' => array(
			'type' => 'double',
			'visible' => 1,
			'enabled' => 1,
			'position' => 50
		),

		'delai_from_last_op' => array(
			'type' => 'integer',
			'label' => 'VehiculeOperationDelay',
			'visible' => 1,
			'enabled' => 1,
			'position' => 60,
			'comment' => 'delay from last operation in months'
		),

		'date_done' => array(
			'type' => 'date',
			'label' => 'VehiculeOperationLastDateDone',
			'visible' => 1,
			'enabled' => 1,
			'position' => 70,
		),

		'km_done' => array(
			'type' => 'double',
			'label' => 'VehiculeOperationLastKmDone',
			'visible' => 1,
			'enabled' => 1,
			'position' => 80,
		),

		'date_next' => array(
			'type' => 'date',
			'label' => 'VehiculeOperationDateNext',
			'visible' => 1,
			'enabled' => 1,
			'position' => 85,
		),

		'km_next' => array(
			'type' => 'double',
			'label' => 'VehiculeOperationKmNext',
			'visible' => 1,
			'enabled' => 1,
			'position' => 90,
		),

		'on_time' => array(
			'type' => 'integer',
			'label' => 'VehiculeOperationOnTime',
			'visible' => 1,
			'enabled' => 1,
			'position' => 95,
		),

		'or_next' => array(
			'type' => 'integer',
			'label' => 'VehiculeOperationNextOR',
			'visible' => 1,
			'enabled' => 1,
			'position' => 100,
			'default' => null,
		)

	);

	/**
	 * doliFleetVehiculeOperation constructor.
	 * @param DoliDB $db Database connector
	 */
	public function __construct($db)
	{
		global $conf;

		parent::__construct($db);

		$this->init();

		$this->date_next = null;
		$this->date_done = null;

		$this->entity = $conf->entity;
	}

	/**
	 * @param int $mode 0=Long label, 1=Short label, 2=Picto + Short label, 3=Picto, 4=Picto + Long label, 5=Short label + Picto, 6=Long label + Picto
	 * @return string
	 */
	public function getLibStatut($mode = 0)
	{
		return self::LibStatut($this->status, $mode);
	}

	/**
	 * @param int $status Status
	 * @param int $mode 0=Long label, 1=Short label, 2=Picto + Short label, 3=Picto, 4=Picto + Long label, 5=Short label + Picto, 6=Long label + Picto
	 * @return string
	 */
	public static function LibStatut($status, $mode)
	{
		global $langs;

		$langs->load('dolifleet@dolifleet');
		$res = '';

		if ($status == self::STATUS_DRAFT) {
			$statusType = 'status0';
			$statusLabel = $langs->trans('doliFleetOperationStatusDraft');
			$statusLabelShort = $langs->trans('doliFleetOperationStatusShortDraft');
		} elseif ($status == self::STATUS_TOPLAN) {
			$statusType = 'status6';
			$statusLabel = $langs->trans('doliFleetOperationStatusToPlan');
			$statusLabelShort = $langs->trans('doliFleetOperationStatusShortToPlan');
		} elseif ($status == self::STATUS_PLANNED) {
			$statusType = 'status1';
			$statusLabel = $langs->trans('doliFleetOperationStatusPlanned');
			$statusLabelShort = $langs->trans('doliFleetOperationStatusShortPlanned');
		} elseif ($status == self::STATUS_DONE) {
			$statusType = 'status4';
			$statusLabel = $langs->trans('doliFleetOperationStatusDone');
			$statusLabelShort = $langs->trans('doliFleetOperationStatusShortDone');
		}

		if (function_exists('dolGetStatus')) {
			$res = dolGetStatus($statusLabel, $statusLabelShort, '', $statusType, $mode);
		} else {
			if ($mode == 0) $res = $statusLabel;
			elseif ($mode == 1) $res = $statusLabelShort;
			elseif ($mode == 2) $res = img_picto($statusLabel, $statusType) . $statusLabelShort;
			elseif ($mode == 3) $res = img_picto($statusLabel, $statusType);
			elseif ($mode == 4) $res = img_picto($statusLabel, $statusType) . $statusLabel;
			elseif ($mode == 5) $res = $statusLabelShort . img_picto($statusLabel, $statusType);
			elseif ($mode == 6) $res = $statusLabel . img_picto($statusLabel, $statusType);
		}

		return $res;
	}

	public function getName()
	{
		$ret = $this->fetch_product();
		if ($ret > 0) return $this->product->getNomUrl(1);
		else return '';
	}

	public function create(User &$user, $notrigger = false)
	{
		global $langs;

		if (empty($this->fk_vehicule) || $this->fk_vehicule == '-1') {
			$this->errors[] = $langs->trans('ErrInvalidFkVehicule');
		}

		if (empty($this->fk_product)) {
			$this->errors[] = $langs->trans('ErrOperationNoProdId');
		}

		require_once DOL_DOCUMENT_ROOT . "/product/class/product.class.php";
		$prod = new Product($this->db);
		$ret = $prod->fetch($this->fk_product);
		if ($ret <= 0) {
			$this->errors[] = $langs->trans('ErrOperationInvalidProduct');
		}

		if (empty($this->km) && empty($this->delai_from_last_op)) {
			$this->errors[] = $langs->trans('ErrOperationNoCritera');
		}

		$this->calcNextOpe();

		if (!empty($this->errors)) return -1;

		if (empty($this->id)) $this->rang = (int)$this->getMaxRank() + 1;

		return parent::create($user, $notrigger);
	}

	public function update(User &$user, $notrigger = false)
	{
		$this->calcNextOpe();
		return parent::update($user, $notrigger);
	}

	public function getMaxRank()
	{
		$sql = "SELECT MAX(rang) as maxrank FROM " . MAIN_DB_PREFIX . $this->table_element . " WHERE fk_vehicule = " . $this->fk_vehicule;
		$resql = $this->db->query($sql);
		if ($resql) {
			$obj = $this->db->fetch_object($resql);
			return $obj->maxrank;
		}

		return 0;
	}

	public function calcNextOpe()
	{
		require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
		if (!empty($this->km)) {
			$this->km_next = (int)$this->km_done + (int)$this->km;
		} else {
			$this->km_next=null;
		}

		if (empty($this->km) && !empty($this->delai_from_last_op)) {
			$this->date_next = dol_time_plus_duree($this->date_done, (int)$this->delai_from_last_op, 'm');
		}

		if ($this->date_next<=dol_now() && empty($this->or_next)) {
			$this->on_time=1;
		} else {
			$this->on_time=0;
		}
	}
}
