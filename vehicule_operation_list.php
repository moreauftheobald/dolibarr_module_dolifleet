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

global $conf;

require 'config.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
dol_include_once('dolifleet/class/vehicule.class.php');
dol_include_once('dolifleet/class/vehiculeOperation.class.php');
dol_include_once('operationorder/class/operationorder.class.php');
dol_include_once('dolifleet/class/dictionaryContractType.class.php');
dol_include_once('dolifleet/class/dictionaryVehiculeType.class.php');
dol_include_once('dolifleet/class/dictionaryVehiculeMark.class.php');

if(empty($user->rights->dolifleet->read)) accessforbidden();

$langs->load('abricot@abricot');
$langs->load('dolifleet@dolifleet');

$fk_soc = GETPOST('fk_soc', 'int');
$fk_product = GETPOST('fk_product', 'int');
$or_next = GETPOST('or_next', 'int');
$search_by=GETPOST('search_by', 'alpha');
$sall=GETPOST('sall');
if (!empty($sall)) {
	$_GET['Listview_dolifleet_search_sall'] = $sall;
}


$massaction = GETPOST('massaction', 'alpha');
$action = GETPOST('action', 'alpha');
$confirmmassaction = GETPOST('confirmmassaction', 'alpha');
$toselect = GETPOST('toselect', 'array');

$object = new doliFleetVehicule($db);
$operation = new dolifleetVehiculeOperation($db);
$dictCT = new dictionaryContractType($db);
$dictVT = new dictionaryVehiculeType($db);
$dictVM = new dictionaryVehiculeMark($db);

$hookmanager->initHooks(array('vehiculeoperationlist'));

/*
 * Actions
 */

$parameters=array();
$reshook=$hookmanager->executeHooks('doActions', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend')
{
    $massaction = '';
}

if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter.x','alpha') || GETPOST('button_removefilter','alpha'))
{
	unset($fk_soc);
}

if (empty($reshook))
{
	// do action from GETPOST ...
}


/*
 * View
 */

llxHeader('', $langs->trans('doliFleetVehiculeOperationList'), '', '');

//$type = GETPOST('type');
//if (empty($user->rights->dolifleet->all->read)) $type = 'mine';

$formconfirm = '';

$parameters = array('formConfirm' => $formconfirm);
$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
if (empty($reshook)) $formconfirm .= $hookmanager->resPrint;
elseif ($reshook > 0) $formconfirm = $hookmanager->resPrint;

// Print form confirm
print $formconfirm;

$TfieldList=array();
foreach ($object->fields as $key=>$datafields) {
	$TfieldList[] = 't.'.$key. ' as t_'.$key;
}

foreach ($operation->fields as $key=>$datafields) {
	$TfieldList[] = 'o.'.$key. ' as o_'.$key;
}
$TfieldList[] = 'p.label as p_label';

$fieldList = implode(',', $TfieldList);

$sql = 'SELECT '.$fieldList;

// Add fields from hooks
$parameters=array('sql' => $sql);
$reshook=$hookmanager->executeHooks('printFieldListSelect', $parameters, $object);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;

$sql.= ' FROM '.MAIN_DB_PREFIX.$object->table_element.' t ';
$sql.= ' INNER JOIN  '.MAIN_DB_PREFIX.$operation->table_element.' o ON o.fk_vehicule=t.rowid ';
$sql.= ' INNER JOIN  '.MAIN_DB_PREFIX.$object->table_element.'_extrafields te ON te.fk_object=t.rowid ';
$sql.= ' INNER JOIN  '.MAIN_DB_PREFIX.'product as p ON o.fk_product=p.rowid ';

$sql.= ' WHERE 1=1';
$sql.= ' AND t.entity IN ('.getEntity('dolifleet', 1).') AND t.status = 1';
if($conf->entity !=1) {
	$sql .= " AND o.date_next < '" . $db->idate(dol_time_plus_duree(dol_now(), (int)$conf->global->THEO_NB_MONTH_CHECKING_VEHICULE_BY_ANTICIPATION, 'm')) . "'";
}
if ($conf->entity!=1) {
	$sql .= ' AND t.atelier IN (' . $conf->entity . ')';
}
//if ($type == 'mine') $sql.= ' AND t.fk_user = '.$user->id;
if (!empty($fk_soc) && $fk_soc > 0) $sql.= ' AND t.fk_soc = '.$fk_soc;
if (!empty($fk_product) && $fk_product > 0) $sql.= ' AND o.fk_product = '.$fk_product;
if (!empty($or_next) && $or_next > 0) $sql.= ' AND o.or_next > 0 AND o.or_next IS NOT NULL';

// Add where from hooks
$parameters=array('sql' => $sql);
$reshook=$hookmanager->executeHooks('printFieldListWhere', $parameters, $object);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;

$formcore = new TFormCore($_SERVER['PHP_SELF'], 'form_list_dolifleet', 'GET');
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
$form = new Form($db);

$nbLine = GETPOST('limit');
if (empty($nbLine)) $nbLine = !empty($user->conf->MAIN_SIZE_LISTE_LIMIT) ? $user->conf->MAIN_SIZE_LISTE_LIMIT : $conf->global->MAIN_SIZE_LISTE_LIMIT;

// configuration listView

$TTitle = array();

foreach ($object->fields as $fieldKey => $infos)
{
	if (isset($infos['label']) && $infos['visible'] > 0) $TTitle['t_'.$fieldKey] = $langs->trans($infos['label']);
}

foreach ($operation->fields as $fieldKey => $infos)
{
	if (isset($infos['label']) && $infos['visible'] > 0) $TTitle['o_'.$fieldKey] = $langs->trans($infos['label']);
}
unset($TTitle['o_fk_vehicule']);
$TTitle['p_label'] = $langs->trans('libelle');

$listViewConfig = array(
	'view_type' => 'list' // default = [list], [raw], [chart]
	,'allow-fields-select' => true
	,'limit'=>array(
		'nbLine' => $nbLine
	)
	,'list' => array(
		'title' => $langs->trans('doliFleetVehiculeOperationList')
		,'image' => 'title_generic.png'
		,'picto_precedent' => '<'
		,'picto_suivant' => '>'
		,'noheader' => 0
		,'messageNothing' => $langs->trans('NodoliFleet')
		,'picto_search' => img_picto('', 'search.png', '', 0)
		,'massactions'=>array(
//			'yourmassactioncode'  => $langs->trans('YourMassActionLabel')
		)
		,'param_url' => '&limit='.$nbLine
	)
	,'subQuery' => array()
	,'link' => array()
	,'type' => array(
		't_date_creation' => 'date' // [datetime], [hour], [money], [number], [integer]
		,'t_tms' => 'date'
		,'t_date_immat'=>'date'
		,'t_date_customer_exploit'=>'date'
		,'t_km_date'=>'date'
		,'t_date_end_contract'=>'date'
		,'o_date_done'=>'date'
		,'o_date_next'=>'date'
		,'o_date_due'=>'date'
	)
	,'search' => array(
		't_vin' => array('search_type' => true, 'table' => 't', 'field' => 'vin')
		,'t_fk_vehicule_type' => array('search_type' => $dictVT->getAllActiveArray('label'),'field'=>'fk_vehicule_type','table' => 't')
		,'t_fk_vehicule_mark' => array('search_type' => $dictVM->getAllActiveArray('label'),'field'=>'fk_vehicule_mark','table' => 't')
		,'t_immatriculation' => array('search_type' => true, 'table' => 't' ,'field' => 'immatriculation')
		,'t_date_immat' => array('search_type' => 'calendars', 'allow_is_null' => false,'table' => 't' ,'field' => 'date_immat')
		,'t_fk_soc' => array('search_type' => 'override', 'override'=> $form->select_company($fk_soc, 'fk_soc'))
		,'t_date_customer_exploit' => array('search_type' => 'calendars', 'allow_is_null' => false,'table' => 't' ,'field' => 'date_customer_exploit')
		,'t_km' => array('search_type' => true, 'table' => 't', 'field' => 'km')
		,'sall' => array('search_type' => true, 'table' => 't', 'field' => array('vin','immatriculation'))
		,'t_km_date' => array('search_type' => 'calendars', 'allow_is_null' => false,'table' => 't' ,'field' => 'km_date')
		,'t_fk_contract_type' => array('search_type' => $dictCT->getAllActiveArray('label'),'table' => 't' ,'field' => 'fk_contract_type')
		,'t_date_end_contract' => array('search_type' => 'calendars', 'allow_is_null' => false,'table' => 't' ,'field' => 'date_end_contract')
		,'o_fk_product' =>  array('search_type' => 'override', 'override'=> $form->select_produits($fk_product, 'fk_product', '1', 0, 0, 1, 2, '', 0, array(), 0, '1', 0, '', 1, '', null, 1))
		,'p_label' => array('search_type' => true, 'table' => 'p', 'field' => 'label')
		,'o_km' =>  array('search_type' => true, 'table' => 'o', 'field' => 'km')
		,'o_delai_from_last_op' =>  array('search_type' => true, 'table' => 'o', 'field' => 'delai_from_last_op')
		,'o_date_done' =>  array('search_type' => 'calendars', 'table' => 'o', 'field' => 'date_done')
		,'o_km_done' =>  array('search_type' => true, 'table' => 'o', 'field' => 'km_done')
		,'o_date_next' =>  array('search_type' => 'calendars', 'table' => 'o', 'field' => 'date_next')
		,'o_date_due' =>  array('search_type' => 'calendars', 'table' => 'o', 'field' => 'date_due')
		,'o_km_next' =>  array('search_type' => true, 'table' => 'o', 'field' => 'km_next')
		,'o_on_time' =>  array('search_type' => (array('1'=>$langs->trans('VehiculeOperationOnTime'))), 'table' => 'o', 'field' => 'on_time',)
		,'o_or_next' =>  array('search_type' => 'override', 'override'=>$form->selectarray('or_next',array(0=>$langs->trans('Empty'),1=>$langs->trans('NotEmpty')),$or_next,1))
	)
	,'translate' => array()
	,'hide' => array(
		't_rowid' // important : rowid doit exister dans la query sql pour les checkbox de massaction
		,'o_rowid' // important : rowid doit exister dans la query sql pour les checkbox de massaction
		,'o_fk_vehicule'
	)
	,'title'=>$TTitle
	,'eval'=>array(
		't_vin' => '_getObjectNomUrl(\'@t_rowid@\', \'@val@\')'
		,'t_fk_vehicule_type' => '_getValueFromId("@val@", "dictionaryVehiculeType")'
		,'t_fk_vehicule_mark' => '_getValueFromId("@val@", "dictionaryVehiculeMark")'
		,'t_fk_soc'			=> '_getSocieteNomUrl("@val@")'
		,'t_fk_contract_type' => '_getValueFromId("@val@", "dictionaryContractType")'
		,'o_fk_product' => '_getProductNomUrl("@val@")'
		,'o_on_time' => '_getBadgeLate("@val@")'
		,'o_or_next' => '_getORNomUrl("@val@")'
	), 'sortfield'=> 'o.date_next', 'sortorder' => 'asc'
);

$r = new Listview($db, 'dolifleet');

// Change view from hooks
$parameters=array(  'listViewConfig' => $listViewConfig);
$reshook=$hookmanager->executeHooks('listViewConfig',$parameters,$r);    // Note that $action and $object may have been modified by hook
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
if ($reshook>0)
{
    $listViewConfig = $hookmanager->resArray;
}


echo $r->render($sql, $listViewConfig);

$parameters=array('sql'=>$sql);
$reshook=$hookmanager->executeHooks('printFieldListFooter', $parameters, $object);    // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

$formcore->end_form();

llxFooter('');
$db->close();

/**
 * TODO remove if unused
 */
function _getObjectNomUrl($id)
{
	global $db;

	$o = new doliFleetVehicule($db);
	$res = $o->fetch($id, false);
	if ($res > 0)
	{
		return $o->getNomUrl(1);
	}

	return '';
}


function _getSocieteNomUrl($fk_soc)
{
	global $db;

	$soc = new Societe($db);
	if ($soc->fetch($fk_soc) > 0)
	{
		return $soc->getNomUrl(1);
	}

	return '';
}

function _getProductNomUrl($fk_product)
{
	global $db;

	$prod = new Product($db);
	if ($prod->fetch($fk_product) > 0)
	{
		return $prod->getNomUrl(1);
	}

	return '';
}
function _getORNomUrl($fk_or)
{
	global $db;

	$or = new OperationOrder($db);
	if (!empty($fk_or)) {
		if ($or->fetch($fk_or,false) > 0) {
			return $or->getNomUrl(1);
		}
	}

	return '';
}

function _getValueFromId($id, $dictionaryClassname)
{
	global $db;

	if (class_exists($dictionaryClassname))
	{
		$dict = new $dictionaryClassname($db);
		return $dict->getValueFromId($id, 'label');
	}
	else return '';
}

function _evalEF($key, $val)
{
	global $extrafields;

	return $extrafields->showOutputField($key, $val);
}

function _getBadgeLate($val) {
	global $langs;
	return (!empty($val)?dolGetBadge($langs->trans('VehiculeOperationOnTime'),'','danger'):'');
}
