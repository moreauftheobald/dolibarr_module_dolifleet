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
	require_once dirname(__FILE__).'/../config.php';
}

class dolifleetVehiculeOperationNp extends SeedObject
{
	/** @var string $table_element Table name in SQL */
	public $table_element = 'dolifleet_vehicule_operation_np';

	/** @var string $element Name of the element (tip for better integration in Dolibarr: this value should be the reflection of the class name with ucfirst() function) */
	public $element = 'dolifleet_vehicule_operation_np';

	public $fk_vehicule;

	public $fk_product;


	public $fields = array(
		'fk_vehicule' => array(
			'type' => 'integer:Vehicule:dolifleet/class/vehicule.class.php',
			'label' => 'Vehicule',
			'visible' => 1,
			'enabled' => 1,
			'notnull' => 1,
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
	);

	/**
	 * doliFleetVehiculeActivity constructor.
	 * @param DoliDB    $db    Database connector
	 */
	public function __construct($db)
	{
		global $conf;

		parent::__construct($db);

		$this->init();

		$this->entity = $conf->entity;
	}

	public function getName()
	{
		$ret = $this->fetch_product();
		if ($ret > 0) return $this->product->getNomUrl(1) . ' ' . $this->product->label;
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

		if (!empty($this->errors)) return -1;

		return parent::create($user, $notrigger);
	}
}
