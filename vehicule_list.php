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

require 'config.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
dol_include_once('dolifleet/class/vehicule.class.php');
dol_include_once('dolifleet/class/dictionaryContractType.class.php');
dol_include_once('dolifleet/class/dictionaryVehiculeType.class.php');
dol_include_once('dolifleet/class/dictionaryVehiculeMark.class.php');

if (empty($user->rights->dolifleet->read)) accessforbidden();

$langs->load('abricot@abricot');
$langs->load('dolifleet@dolifleet');

$fk_soc = GETPOST('fk_soc', 'int');
$search_by = GETPOST('search_by', 'alpha');
$sall = GETPOST('search_all');
if (!empty($sall)) {
	$_GET['Listview_dolifleet_search_sall'] = $sall;
}


$massaction = GETPOST('massaction', 'alpha');
$action = GETPOST('action', 'alpha');
$confirmmassaction = GETPOST('confirmmassaction', 'alpha');
$toselect = GETPOST('toselect', 'array');

$object = new doliFleetVehicule($db);
$dictCT = new dictionaryContractType($db);
$dictVT = new dictionaryVehiculeType($db);
$dictVM = new dictionaryVehiculeMark($db);

$hookmanager->initHooks(array('vehiculelist'));

if ($object->isextrafieldmanaged) {
	$extrafields = new ExtraFields($db);
	$extralabels = $extrafields->fetch_name_optionals_label($object->table_element);
}

/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') {
	$massaction = '';
}

if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
	unset($fk_soc);
}

if (empty($reshook)) {
	// do action from GETPOST ...
}


/*
 * View
 */

llxHeader('', $langs->trans('doliFleetVehiculeList'), '', '');

//$type = GETPOST('type');
//if (empty($user->rights->dolifleet->all->read)) $type = 'mine';

$formconfirm = '';

$parameters = array('formConfirm' => $formconfirm);
$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
if (empty($reshook)) $formconfirm .= $hookmanager->resPrint;
elseif ($reshook > 0) $formconfirm = $hookmanager->resPrint;

// Print form confirm
print $formconfirm;

$object->fields['type_custom']['visible'] = $user->rights->dolifleet->extended_read ? 1 : 0;
$object->fields['coutm']['visible'] = $user->rights->dolifleet->extended_read ? 1 : 0;
$object->fields['date_fin_fin']['visible'] = $user->rights->dolifleet->extended_read ? 1 : 0;
$object->fields['type_fin']['visible'] = $user->rights->dolifleet->extended_read ? 1 : 0;
$object->fields['com_custom']['visible'] = $user->rights->dolifleet->extended_read ? 1 : 0;
$object->fields['date_fin_loc']['visible'] = $user->rights->dolifleet->extended_read ? 1 : 0;
$object->fields['exit_data']['visible'] = $user->rights->dolifleet->extended_read ? 1 : 0;
$object->fields['age_veh']['visible'] = $user->rights->dolifleet->extended_read ? 1 : 0;

// TODO ajouter les champs de son objet que l'on souhaite afficher
$keys = array_keys($object->fields);
$fieldList = 't.' . implode(', t.', $keys);

if (!empty($object->isextrafieldmanaged)) {
	$keys = array_keys($extralabels);
	if (!empty($keys)) {
		$fieldList .= ', et.' . implode(', et.', $keys);
	}
}

$sql = 'SELECT ' . $fieldList;

// Add fields from hooks
$parameters = array('sql' => $sql);
$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters, $object);    // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;

$sql .= ' FROM ' . MAIN_DB_PREFIX . $object->table_element . ' t ';

if (!empty($object->isextrafieldmanaged) && array_keys($extralabels)) {
	$sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . $object->table_element . '_extrafields et ON (et.fk_object = t.rowid)';
}

$sql .= ' WHERE 1=1';
$sql .= ' AND t.entity IN (' . getEntity('dolifleet', 1) . ')';
//if ($type == 'mine') $sql.= ' AND t.fk_user = '.$user->id;
if (!empty($fk_soc) && $fk_soc > 0) $sql .= ' AND t.fk_soc = ' . $fk_soc;
if (GETPOSTISSET('dim_pneu')) {
	$sql .= ' AND t.dim_pneu IN (' . implode(',', GETPOST('dim_pneu', 'array')) . ')';
}
if (GETPOSTISSET('atelier') && GETPOST('atelier', 'int') > 0) {
	$sql .= ' AND t.atelier = ' . GETPOST('atelier', 'int');
}
$sql .= ' AND t.entity IN (' . getEntity('dolifleet', 1) . ')';

// Add where from hooks
$parameters = array('sql' => $sql);
$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters, $object);    // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;

//print $sql;

$formcore = new TFormCore($_SERVER['PHP_SELF'], 'form_list_dolifleet', 'GET');
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
$form = new Form($db);

$nbLine = GETPOST('limit');
if (empty($nbLine)) $nbLine = !empty($user->conf->MAIN_SIZE_LISTE_LIMIT) ? $user->conf->MAIN_SIZE_LISTE_LIMIT : $conf->global->MAIN_SIZE_LISTE_LIMIT;

// configuration listView

$TTitle = array();

foreach ($object->fields as $fieldKey => $infos) {
	if (isset($infos['label']) && $infos['visible'] > 0) $TTitle[$fieldKey] = $langs->trans($infos['label']);
}

$TTitle['status'] = $langs->trans('Status');

if (!empty(array_keys($extralabels))) {
	foreach ($extralabels as $k => $v) {
		$permit = dol_eval($extrafields->attributes[$object->table_element]['list'][$k], 1);
		if (in_array(abs($permit), array(1, 2, 4)) && !empty(abs($permit))) {
			$TTitle[$k] = $v;
		}
	}
}

$listViewConfig = array(
	'view_type' => 'list' // default = [list], [raw], [chart]
, 'allow-fields-select' => true
, 'limit' => array(
		'nbLine' => $nbLine
	)
, 'list' => array(
		'title' => $langs->trans('doliFleetVehiculeList')
	, 'image' => 'title_generic.png'
	, 'picto_precedent' => '<'
	, 'picto_suivant' => '>'
	, 'noheader' => 0
	, 'messageNothing' => $langs->trans('NodoliFleet')
	, 'picto_search' => img_picto('', 'search.png', '', 0)
	, 'massactions' => array(//			'yourmassactioncode'  => $langs->trans('YourMassActionLabel')
		)
	, 'param_url' => '&limit=' . $nbLine
	)
, 'subQuery' => array()
, 'link' => array()
, 'type' => array(
		'date_creation' => 'date' // [datetime], [hour], [money], [number], [integer]
	, 'tms' => 'date'
	, 'date_immat' => 'date'
	, 'km_date' => 'date'
	, 'date_end_contract' => 'date'
	)
, 'search' => array(
		'vin' => array('search_type' => true, 'table' => 't', 'field' => 'vin')
	, 'fk_vehicule_type' => array('search_type' => $dictVT->getAllActiveArray('label'))
	, 'fk_vehicule_mark' => array('search_type' => $dictVM->getAllActiveArray('label'))
	, 'immatriculation' => array('search_type' => true, 'table' => 't', 'field' => 'immatriculation')
	, 'date_immat' => array('search_type' => 'calendars', 'allow_is_null' => false)
	, 'fk_soc' => array('search_type' => 'override', 'override' => $form->select_company($fk_soc, 'fk_soc'))
	, 'km' => array('search_type' => true, 'table' => 't', 'field' => 'km')
	, 'sall' => array('search_type' => true, 'table' => 't', 'field' => array('vin', 'immatriculation'))
	, 'km_date' => array('search_type' => 'calendars', 'allow_is_null' => false)
	, 'fk_contract_type' => array('search_type' => $dictCT->getAllActiveArray('label'))
	, 'date_end_contract' => array('search_type' => 'calendars', 'allow_is_null' => false)
	, 'nb_pneu' => array('search_type' => true, 'table' => 't', 'field' => 'nb_pneu')
	, 'status' => array('search_type' => doliFleetVehicule::$TStatus, 'to_translate' => true)
	, 'dim_pneu' => array('search_type' => 'override', 'override' => $form->multiselectarray('dim_pneu', $object->fields['dim_pneu']['arrayofkeyval'], GETPOST('dim_pneu', 'array'), '', 0, '', 0, '100%'))
	)
, 'translate' => array()
, 'hide' => array(
		'rowid' // important : rowid doit exister dans la query sql pour les checkbox de massaction
	)
, 'title' => $TTitle
, 'eval' => array(
		'vin' => '_getObjectNomUrl(\'@rowid@\', \'@val@\')'
	, 'status' => 'doliFleetVehicule::LibStatut("@val@", 5)' // Si on a un fk_user dans notre requête
	, 'dim_pneu' => '_getDimPneu("@val@")' // Si on a un fk_user dans notre requête
	)
);

if (!empty($extralabels)) {
	foreach ($extralabels as $k => $v) {
		$permit = dol_eval($extrafields->attributes[$object->table_element]['list'][$k], 1);
		if (in_array(abs($permit), array(1, 2, 4)) && !empty(abs($permit))) {
			$listViewConfig['eval'][$k] = '_evalEF("' . $k . '", "@val@", "' . $object->table_element . '")';
		}
	}
}

if ($user->rights->dolifleet->extended_read) {
	$listViewConfig['search']['type_custom'] = array('search_type' => true, 'table' => 't', 'field' => 'type');
	$listViewConfig['search']['coutm'] = array('search_type' => true, 'table' => 't', 'field' => 'coutm');
	$listViewConfig['search']['date_fin_fin'] = array('search_type' => 'calendars', 'allow_is_null' => false);
	$listViewConfig['search']['type_fin'] = array('search_type' => true, 'table' => 't', 'field' => 'type_fin');
	$listViewConfig['search']['com_custom'] = array('search_type' => true, 'table' => 't', 'field' => 'com_custom');
	$listViewConfig['search']['date_fin_loc'] = array('search_type' => 'calendars', 'allow_is_null' => false);
	$listViewConfig['search']['exit_data'] = array('search_type' => 'calendars', 'allow_is_null' => false);
	$listViewConfig['search']['age_veh'] = array('search_type' => true, 'table' => 't', 'field' => 'age_veh');
}

foreach ($object->fields as $key => $field) {
	if (!isset($listViewConfig['eval'][$key])) {
		$listViewConfig['eval'][$key] = '_getObjectOutputField(\'' . $key . '\', \'@val@\')';
	}
}


$r = new Listview($db, 'dolifleet');

// Change view from hooks
$parameters = array('listViewConfig' => $listViewConfig);
$reshook = $hookmanager->executeHooks('listViewConfig', $parameters, $r);    // Note that $action and $object may have been modified by hook
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
if ($reshook > 0) {
	$listViewConfig = $hookmanager->resArray;
}
echo $r->render($sql, $listViewConfig);

$parameters = array('sql' => $sql);
$reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters, $object);    // Note that $action and $object may have been modified by hook
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
	if ($res > 0) {
		return $o->getNomUrl(1);
	}

	return '';
}

/**
 * TODO remove if unused
 */

function _evalEF($key, $val, $extrafieldsobjectkey)
{
	global $extrafields;

	return $extrafields->showOutputField($key, $val, '', $extrafieldsobjectkey);
}

function _getObjectOutputField($key, $value)
{
	global $db, $user;
	$object = new doliFleetVehicule($db);
	$object->fields['type_custom']['visible'] = $user->rights->dolifleet->extended_read ? 1 : 0;
	$object->fields['coutm']['visible'] = $user->rights->dolifleet->extended_read ? 1 : 0;
	$object->fields['date_fin_fin']['visible'] = $user->rights->dolifleet->extended_read ? 1 : 0;
	$object->fields['type_fin']['visible'] = $user->rights->dolifleet->extended_read ? 1 : 0;
	$object->fields['com_custom']['visible'] = $user->rights->dolifleet->extended_read ? 1 : 0;
	$object->fields['date_fin_loc']['visible'] = $user->rights->dolifleet->extended_read ? 1 : 0;
	$object->fields['exit_data']['visible'] = $user->rights->dolifleet->extended_read ? 1 : 0;
	$object->fields['age_veh']['visible'] = $user->rights->dolifleet->extended_read ? 1 : 0;
	return $object->showOutputField($object->fields[$key], $key, $value);
}

function _getDimPneu($value)
{

	global $db;
	$object = new doliFleetVehicule($db);

	$output = '<div class="select2-container-multi-dolibarr" style="width: 90%;"><ul class="select2-choices-dolibarr">';
	$selected = explode(',', $value);
	foreach ($selected as $sel) {
		$output .= '<li class="select2-search-choice-dolibarr noborderoncategories" style="background: #bbb">' .
			$object->fields['dim_pneu']['arrayofkeyval'][$sel] .
			'</li>';
	}
	$output .= '</ul></div>';

	return $output;
}
