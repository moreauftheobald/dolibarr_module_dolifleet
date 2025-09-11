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

/**
 *    \file        lib/dolifleet.lib.php
 *    \ingroup    dolifleet
 *    \brief        This file is an example module library
 *                Put some comments here
 */

/**
 * @return array
 */
function dolifleetAdminPrepareHead()
{
	global $langs, $conf;

	$langs->load('dolifleet@dolifleet');

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/dolifleet/admin/dolifleet_setup.php", 1);
	$head[$h][1] = $langs->trans("Parameters");
	$head[$h][2] = 'settings';
	$h++;

	if (getDolGlobalInt('DOLIFLEET_USE_RENTAL_FEATURE')) {
		$head[$h][0] = dol_buildpath("/dolifleet/admin/rental_matrix.php", 1);
		$head[$h][1] = $langs->trans("rentalMatrix");
		$head[$h][2] = 'matrix';
		$h++;
	}

	$head[$h][0] = dol_buildpath("/dolifleet/admin/vehicule_extrafields.php", 1);
	$head[$h][1] = $langs->trans("ExtraFields");
	$head[$h][2] = 'extrafields';
	$h++;

	if (!empty(isModEnabled("multicompany"))) {
		$head[$h][0] = dol_buildpath("/dolifleet/admin/multicompany_sharing.php", 1);
		$head[$h][1] = $langs->trans("multicompanySharing");
		$head[$h][2] = 'multicompanySharing';
		$h++;
	}

	$head[$h][0] = dol_buildpath("/dolifleet/admin/dolifleet_about.php", 1);
	$head[$h][1] = $langs->trans("About");
	$head[$h][2] = 'about';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@dolifleet:/dolifleet/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@dolifleet:/dolifleet/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'dolifleet');

	return $head;
}

/**
 * Return array of tabs to used on pages for third parties cards.
 *
 * @param doliFleetVehicule $object Object company shown
 * @return    array                Array of tabs
 */
function vehicule_prepare_head(doliFleetVehicule $object)
{
	global $langs, $conf, $db;
	$h = 0;
	$head = array();
	$head[$h][0] = dol_buildpath('/dolifleet/vehicule_card.php', 1) . '?id=' . $object->id;
	$head[$h][1] = $langs->trans("doliFleetVehiculeCard");
	$head[$h][2] = 'card';
	$h++;

	require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT . '/core/class/link.class.php';
	$upload_dir = $conf->operationorder->multidir_output[$object->entity ? $object->entity : $conf->entity] . "/vehicule/" . dol_sanitizeFileName($object->ref);
	$nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
	$nbLinks = Link::count($db, $object->element, $object->id);
	$head[$h][0] = dol_buildpath("/dolifleet/vehicule_document.php", 1) . '?id=' . $object->id;
	$head[$h][1] = $langs->trans('Documents');
	if (($nbFiles + $nbLinks) > 0) $head[$h][1] .= '<span class="badge marginleftonlyshort">' . ($nbFiles + $nbLinks) . '</span>';
	$head[$h][2] = 'document';
	$h++;
	$nbOperationOrder = getNbORVehicle($object->id);
	$head[$h][0] = dol_buildpath('operationorder/list.php?&origin=vehicule&originid=' . $object->id, 1);
	$head[$h][1] = $langs->trans('ORListHisto') . '<span class="badge marginleftonlyshort">' . ($nbOperationOrder >= 0 ? $nbOperationOrder : 0) . '</span>';
	$head[$h][2] = 'list';


	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@dolifleet:/dolifleet/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname:Title:@dolifleet:/dolifleet/mypage.php?id=__ID__');   to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'dolifleetVehicule');

	return $head;
}

/**
 * Return array of tabs to used on pages for third parties cards.
 *
 * @param dolifleetRentalProposal $object Object company shown
 * @return    array                Array of tabs
 */
function rental_proposal_prepare_head(dolifleetRentalProposal $object)
{
	global $langs, $conf;
	$h = 0;
	$head = array();
	$head[$h][0] = dol_buildpath('/dolifleet/rental_proposal_card.php', 1) . '?id=' . $object->id;
	$head[$h][1] = $langs->trans("doliFleetRentalProposalCard");
	$head[$h][2] = 'card';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@dolifleet:/dolifleet/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname:Title:@dolifleet:/dolifleet/mypage.php?id=__ID__');   to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'dolifleetRentalProposal');

	return $head;
}

/**
 * @param Form $form Form object
 * @param doliFleet $object doliFleet object
 * @param string $action Triggered action
 * @return string
 */
function getFormConfirmdoliFleetVehicule($form, $object, $action)
{
	global $langs, $user;

	$formconfirm = '';

	if ($action === 'valid' && !empty($user->hasRight("dolifleet","write"))) {
		$body = $langs->trans('ConfirmActivatedoliFleetVehiculeBody', $object->immatriculation);
		$formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('ConfirmActivatedoliFleetVehiculeTitle'), $body, 'confirm_validate', '', 0, 1);
	} elseif ($action === 'validate' && !empty($user->hasRight("dolifleet","write"))) {
		$body = $langs->trans('ConfirmValidateRentalProposalBody');
		$formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('ConfirmValidateRentalProposalTitle'), $body, 'confirm_validate', '', 0, 1);
	} elseif ($action === 'accept' && !empty($user->hasRight("dolifleet","write"))) {
		$body = $langs->trans('ConfirmAcceptRentalProposalBody');
		$formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('ConfirmAcceptRentalProposalTitle'), $body, 'confirm_accept', '', 0, 1);
	} elseif ($action === 'close' && !empty($user->hasRight("dolifleet","write"))) {
		$body = $langs->trans('ConfirmCloseRentalProposalBody');
		$formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('ConfirmCloseRentalProposalTitle'), $body, 'confirm_close', '', 0, 1);
	} elseif ($action === 'modif' && !empty($user->hasRight("dolifleet","write"))) {
		$body = $langs->trans('ConfirmReopendoliFleetVehiculeBody', $object->immatriculation);
		$formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('ConfirmReopendoliFleetVehiculeTitle'), $body, 'confirm_modif', '', 0, 1);
	} elseif ($action === 'delete' && !empty($user->hasRight("dolifleet","delete"))) {
		$body = $langs->trans('ConfirmDeletedoliFleetVehiculeBody');
		$formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('ConfirmDeletedoliFleetVehiculeTitle'), $body, 'confirm_delete', '', 0, 1);
	} elseif ($action === 'deleteRental' && !empty($user->hasRight("dolifleet","delete"))) {
		$body = $langs->trans('ConfirmDeleteRentalBody');
		$formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('ConfirmDeletedoliFleetVehiculeTitle'), $body, 'confirm_delete', '', 0, 1);
	} elseif ($action === 'clone' && !empty($user->hasRight("dolifleet","write"))) {
		$body = $langs->trans('ConfirmClonedoliFleetVehiculeBody', $object->immatriculation);
		$formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('ConfirmClonedoliFleetVehiculeTitle'), $body, 'confirm_clone', '', 0, 1);
	} elseif ($action === 'delActivity' && !empty($user->hasRight("dolifleet","write"))) {
		$body = $langs->trans('ConfirmDelActivitydoliFleetVehiculeBody');
		$formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id . '&act_id=' . GETPOST('act_id'), $langs->trans('ConfirmDeletedoliFleetVehiculeTitle'), $body, 'confirm_delActivity', '', 0, 1);
	} elseif ($action === 'unlinkVehicule' && !empty($user->hasRight("dolifleet","write"))) {
		$body = $langs->trans('ConfirmUnlinkVehiculedoliFleetVehiculeBody');
		$formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id . '&linkVehicule_id=' . GETPOST('linkVehicule_id'), $langs->trans('ConfirmUnlinkVehiculedoliFleetVehiculeTitle'), $body, 'confirm_unlinkVehicule', '', 0, 1);
	} elseif ($action === 'delRental' && !empty($user->hasRight("dolifleet","write"))) {
		$body = $langs->trans('ConfirmDelRentaldoliFleetVehiculeBody');
		$formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id . '&rent_id=' . GETPOST('rent_id'), $langs->trans('ConfirmDeletedoliFleetVehiculeTitle'), $body, 'confirm_delRental', '', 0, 1);
	} elseif ($action === 'delOperation' && !empty($user->hasRight("dolifleet","write"))) {
		$body = $langs->trans('ConfirmDelOperationdoliFleetVehiculeBody');
		$formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id . '&ope_id=' . GETPOST('ope_id'), $langs->trans('ConfirmDeletedoliFleetVehiculeTitle'), $body, 'confirm_delOperation', '', 0, 1);
	} elseif ($action === 'delOperationNp' && !empty($user->hasRight("dolifleet","write"))) {
		$body = $langs->trans('ConfirmDelOperationdoliFleetVehiculeBody');
		$formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id . '&openp_id=' . GETPOST('openp_id'), $langs->trans('ConfirmDeletedoliFleetVehiculeTitle'), $body, 'confirm_delOperationNp', '', 0, 1);
	} elseif ($action === 'delMatrixLine' && !empty($user->hasRight("dolifleet","write"))) {
		$body = $langs->trans('ConfirmDeldoliFleetLineBody');
		$formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . GETPOST('id'), $langs->trans('ConfirmDeletedoliFleetVehiculeTitle'), $body, 'confirm_delMatrixLine', '', 0, 1);
	}

	return $formconfirm;
}

/**
 * @param doliFleetVehicule $object
 */
function printVehiculeActivities($object, $fromcard = false)
{
	global $langs, $db, $form;

	$dict = new dictionaryVehiculeActivityType($db);
	$TTypeActivity = $dict->getAllActiveArray('label');

	print load_fiche_titre($langs->trans('VehiculeActivities'), '', '');

	if (GETPOST('action', 'alpha') == 'editActivity') {
		$actionForm='updateActivity';
	} else {
		$actionForm='addActivity';
	}

	print '<form id="activityForm" method="POST" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '">';
	print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
	print '<input type="hidden" name="action" value="'.$actionForm.'">';
	print '<input type="hidden" name="id" value="' . $object->id . '">';
	if (!empty(GETPOST('act_id', 'int'))) {
		print '<input type="hidden" name="act_id" value="' . GETPOST('act_id', 'int') . '">';
	}

	print '<table class="border" width="100%">' . "\n";
	print '<tr class="liste_titre">
					<td align="center">' . $langs->trans('ActivityType') . '</td>
					<td align="center">' . $langs->trans('DateStart') . '</td>
					<td align="center">' . $langs->trans('DateEnd') . '</td>
					<td align="center">' . $langs->trans('soc') . '</td>
					<td></td>
					</tr>';

	$date_start = $date_end = '';
	if ($fromcard) {
		$date_start = dol_now();
		$date_end = strtotime("+3 month", $date_start);
	}

	$ret = $object->getActivities($date_start, $date_end);
	if ($ret == 0) {
		print '<tr><td align="center" colspan="5">' . $langs->trans('NodoliFleetActivity') . '</td></tr>';
	} elseif ($ret > 0) {
		/** @var doliFleetVehiculeActivity $activity */
		foreach ($object->activities as $activity) {
			if (GETPOST('action', 'alpha') == 'editActivity'
				&& $activity->id == GETPOST('act_id', 'int')) {
				print '<tr>';
				print '<td align="center">' . $form->selectArray('activityTypes', $TTypeActivity, $activity->fk_type, 1, 0, 0, 'style="width: 100%"') . '</td>';
				print '<td align="center">' . $form->selectDate($activity->date_start, 'activityDate_start') . '</td>';
				print '<td align="center">' . $form->selectDate($activity->date_end, 'activityDate_end') . '</td>';
				print '<td align="center">' .$form->select_thirdparty_list($activity->fk_soc, 'socid', 's.client = 1', '', 0, 0, array(), '', 0, 0, $morecss = '', 'style="width: 80%"'). '</td>';
				print '<td align="center"><input class="button" type="submit" name="addActivity" value="' . $langs->trans("Save") . '"></td>';
				print '</tr>';
			} else {
				print '<tr>';
				print '<td align="center">' . $activity->getType() . '</td>';
				print '<td align="center">' . dol_print_date($activity->date_start, "%d/%m/%Y") . '</td>';
				print '<td align="center">' . (!empty($activity->date_end) ? dol_print_date($activity->date_end, "%d/%m/%Y") : '') . '</td>';
				print '<td align="center">' . $activity->showOutputField($activity->fields['fk_soc'], 'fk_soc', $activity->fk_soc) . '</td>';
				print '<td align="center">';
				print '<a href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=editActivity&act_id=' . $activity->id . '">' . img_edit() . '</a>';
				print '<a href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=delActivity&act_id=' . $activity->id . '">' . img_delete() . '</a>';
				print '</td>';
				print '</tr>';
			}
		}
	}
	if (GETPOST('action', 'alpha') !== 'editActivity'
		&& GETPOST('action', 'alpha') !== 'delActivity') {
		// ligne nouvelle activité
		print '<tr id="newActivity">';
		print '<td align="center">';

		$dict = new dictionaryVehiculeActivityType($db);
		$TTypeActivity = $dict->getAllActiveArray('label');
		print $form->selectArray('activityTypes', $TTypeActivity, GETPOST('activityTypes'), 1);

		print '</td>';

		print '<td align="center">';
		print $form->selectDate('', 'activityDate_start');
		print '</td>';

		print '<td align="center">';
		print $form->selectDate('', 'activityDate_end');
		print '</td>';

		print '<td align="center">';
		print $object->showOutputField($object->fields['fk_soc'], 'fk_soc', $object->fk_soc);
		print '</td>';

		print '<td align="center">';
		print '<input class="button" type="submit" name="addActivity" value="' . $langs->trans("Add") . '">';
		print '</td>';

		print '</tr>';
	}

	print '</table>';

	print '</form>';
	?>
	<script>
		$("#activityTypes").addClass("soixantepercent");
		$("#activityDate_start").addClass("quatrevingtpercent");
		$("#activityDate_end").addClass("quatrevingtpercent");
	</script>
	<?php
}

/**
 * @param doliFleetVehicule $object
 */
function printLinkedVehicules($object, $fromcard = false)
{
	global $langs, $db, $form, $conf;

	print load_fiche_titre($langs->trans('LinkedVehicules'), '', '');

	print '<form id="vehiculeLinkedForm" method="POST" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '">';
	print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
	print '<input type="hidden" name="action" value="addVehiculeLink">';
	print '<input type="hidden" name="id" value="' . $object->id . '">';

	print '<table class="border" width="100%">' . "\n";
	print '<tr class="liste_titre">';
	print '<td align="center">Immatriculation</td>';
	print '<td align="center">' . $langs->trans('DateStart') . '</td>';
	print '<td align="center">' . $langs->trans('DateEnd') . '</td>';
	print '<td align="center"></td>';
	print '</tr>';

	$date_start = $date_end = '';
	if ($fromcard) {
		$date_start = dol_now();
		$date_end = strtotime("+3 month", $date_start);
	}

	$object->getLinkedVehicules();
	if (empty($object->linkedVehicules)) {
		print '<tr><td align="center" colspan="4">' . $langs->trans('NodoliFleet') . '</td></tr>';
	} else {
		foreach ($object->linkedVehicules as $vehiculelink) {
			$veh = new doliFleetVehicule($db);
			print '<tr>';
			print '<td align="center">';

			$veh->fetch($vehiculelink->fk_other_vehicule);

			print $veh->getLinkUrl(0, '', 'immatriculation');
			print '</td>';
			print '<td align="center">' . dol_print_date($vehiculelink->date_start, "%d/%m/%Y") . '</td>';
			print '<td align="center">' . (!empty($vehiculelink->date_end) ? dol_print_date($vehiculelink->date_end, "%d/%m/%Y") : '') . '</td>';
			print '<td align="center"><a href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=unlinkVehicule&linkVehicule_id=' . $vehiculelink->id . '"><span class="fas fa-unlink"></span></a> </td>';
			print '</tr>';
		}
	}

	// new link
	print '<tr">';
	$sql = "SELECT v.rowid, v.immatriculation, vt.label FROM " . MAIN_DB_PREFIX . "dolifleet_vehicule as v";
	$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_dolifleet_vehicule_type as vt ON vt.rowid = v.fk_vehicule_type";
	$sql .= " WHERE v.status = 1";
	$DOLIFLEET_MOTRICE_TYPES = unserialize(getDolGlobalString("DOLIFLEET_MOTRICE_TYPES") );
	if (!empty($DOLIFLEET_MOTRICE_TYPES)) {
		if (in_array($object->fk_vehicule_type, $DOLIFLEET_MOTRICE_TYPES))
			$sql .= " AND v.fk_vehicule_type NOT IN (" . implode(', ', $DOLIFLEET_MOTRICE_TYPES) . ")";
		else $sql .= " AND v.fk_vehicule_type IN (" . implode(', ', $DOLIFLEET_MOTRICE_TYPES) . ")";
	} else {
		// a minima on ne peut lier 2 véhicules de même nature
		$sql .= " AND v.fk_vehicule_type <> " . $object->fk_vehicule_type;
	}
	$sql .= " AND v.fk_soc = " . $object->fk_soc;
	$resql = $db->query($sql);
	$Tab = array();
	if ($resql) {
		while ($obj = $db->fetch_object($resql)) {
			$Tab[$obj->rowid] = $obj->label . ' - ' . $obj->immatriculation;
		}
	}

	print '<td align="center">';
	print $form->selectarray('linkVehicule_id', $Tab, GETPOST('linkVehicule_id'), 1, 0, 0, '', 0, 0, 0, '', '', 1);
	print '</td>';
	print '<td align="center">';
	print $form->selectDate('', 'linkDate_start');
	print '</td>';

	print '<td align="center">';
	print $form->selectDate('', 'linkDate_end');
	print '</td>';

	print '<td align="center">';
	print '<input class="button" type="submit" name="linkVehicule" value="' . $langs->trans("Add") . '">';
	print '</td>';
	print '</tr>';

	print '</table>';

	print '</form>';
}

/**
 * @param doliFleetVehicule $object
 */
function printVehiculeRental($object, $fromcard = false, $external = false)
{
	global $langs, $form, $db;

	$title = $langs->trans('VehiculeRentals');
	if ($external) {
		dol_include_once('dolifleet/class/rentalProposal.class.php');
		$prop = new dolifleetRentalProposal($db);
		$det = new dolifleetRentalProposalDet($db);
		$title .= ' ' . $langs->trans('Customer');
	}

	print load_fiche_titre($title, '', '');

	print '<form id="vehiculeRentalForm" method="POST" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '">';
	print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
	print '<input type="hidden" name="action" value="addVehiculeRental">';
	print '<input type="hidden" name="id" value="' . $object->id . '">';

	print '<table class="border" width="100%">' . "\n";
	print '<tr class="liste_titre">';
	if ($external) {
		print '<td align="center">' . $langs->trans('soc') . '</td>';
	}
	print '<td align="center">' . $langs->trans('DateStart') . '</td>';
	print '<td align="center">' . $langs->trans('DateEnd') . '</td>';
	print '<td align="center">' . $langs->trans('TotalHT') . '</td>';
	if (!$external) {
		print '<td align="center"></td>';
	} else {
		print '<td align="center">' . $langs->trans('Prefac') . '</td>';
	}
	print '</tr>';

	$date_start = $date_end = '';
	if ($fromcard) {
		$date_start = dol_now();
		$date_end = strtotime("+3 month", $date_start);
	}

	$object->getRentals($date_start, $date_end, $external);
	if (empty($object->rentals)) {
		print '<tr>';
		print '<td align="center" colspan="5">' . $langs->trans('NodoliFleet') . '</td>';
		print '</tr>';
	} else {
		foreach ($object->rentals as $rent) {
			print '<tr>';
			if ($external) {
				print '<td align="center">' . $rent->showOutputField($rent->fields['fk_soc'], 'fk_soc', $rent->fk_soc) . '</td>';
			}
			print '<td align="center">';
			print dol_print_date($rent->date_start, "%d/%m/%Y");
			print '</td>';

			print '<td align="center">';
			print dol_print_date($rent->date_end, "%d/%m/%Y");
			print '</td>';

			print '<td align="center">';
			print price($rent->total_ht);
			print '</td>';

			print '<td align="center">';
			if (!$external) {
				print '<a href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=delRental&rent_id=' . $rent->id . '">' . img_delete() . '</a>';
			} else {
				$det->fetch($rent->fk_proposaldet);
				$prop->fetch($det->fk_rental_proposal);
				print $prop->getNomUrl(1);
			}
			print '</td>';

			print '</tr>';
		}
	}

	if (!$external) {
		// new line
		print '<tr>';

		print '<td align="center">';
		print $form->selectDate('', 'RentalDate_start');
		print '</td>';

		print '<td align="center">';
		print $form->selectDate('', 'RentalDate_end');
		print '</td>';

		print '<td align="center">';
		print '<input type="number" name="RentalTotal_HT" min="0" step="0.01" value="' . GETPOST('RentalTotal_HT') . '">';
		print '</td>';

		print '<td align="center">';
		print '<input class="button" type="submit" name="addRental" value="' . $langs->trans("Add") . '">';
		print '</td>';

		print '</tr>';
	}


	print '</table>';

	print '</form>';
}

/**
 * @param doliFleetVehicule $object
 */
function printVehiculeOperations($object)
{
	global $langs, $form;
	dol_include_once('operationorder/class/operationorder.class.php');

	print load_fiche_titre($langs->trans('VehiculeOperations'), '', '');

	if (GETPOST('action', 'alpha') == 'editOperation') {
		$actionForm='updateOperation';
	} else {
		$actionForm='addVehiculeOperation';
	}

	print '<form id="vehiculeOperationsForm" method="POST" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '">';
	print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
	print '<input type="hidden" name="action" value="'.$actionForm.'">';
	print '<input type="hidden" name="id" value="' . $object->id . '">';
	if (!empty(GETPOST('ope_id', 'int'))) {
		print '<input type="hidden" name="ope_id" value="' . GETPOST('ope_id', 'int') . '">';
	}

	print '<table class="border" width="100%">' . "\n";
	print '<tr class="liste_titre">';
	print '<td align="center">' . $langs->trans('VehiculeOperation') . '</td>';
	print '<td align="center">' . $langs->trans('KM') . '</td>';
	print '<td align="center">' . $langs->trans('VehiculeOperationDelay') . '</td>';
	print '<td align="center">' . $langs->trans('VehiculeOperationLastDateDone') . '</td>';
	print '<td align="center">' . $langs->trans('VehiculeOperationLastKmDone') . '</td>';
	print '<td align="center">' . $langs->trans('VehiculeOperationDateNext') . '</td>';
	print '<td align="center">' . $langs->trans('VehiculeOperationKmNext') . '</td>';
	print '<td align="center">' . $langs->trans('VehiculeOperationOnTime') . '</td>';
	print '<td align="center">' . $langs->trans('VehiculeOperationNextOR') . '</td>';
	print '<td align="center"></td>';
	print '</tr>';

	$res = $object->getOperations();
	if ($res < 0) {
		setEventMessage($object->error, 'errors');
	}
	if (empty($object->operations)) {
		print '<tr><td align="center" colspan="6">' . $langs->trans('NodoliFleet') . '</td></tr>';
	} else {
		foreach ($object->operations as $operation) {
			print '<tr>';
			if (GETPOST('action', 'alpha') == 'editOperation'
				&& $operation->id == GETPOST('ope_id', 'int')) {
				print '<td align="center">';
				print $form->select_produits($operation->fk_product, 'productid', 1, 0, 0, 1, 2, '', 0);
				print '</td>';
				print '<td align="center">';
				print '<input class="quatrevingtpercent" type="number" name="km" id="km" step="1" value="' . $operation->km . '">';
				print '</td>';
				print '<td align="center">';
				print '<input class="soixantepercent" type="number" name="delay" id="delay" step="1" value="' . $operation->delai_from_last_op . '">&nbsp;' . $langs->trans('Months');
				print '</td>';
				print '<td align="center">';
				print $form->selectDate($operation->date_done, 'date_done');
				print '</td>';
				print '<td align="center"><input class="quatrevingtpercent" type="number" name="km_done" id="km" step="1" value="' . $operation->km_done . '"></td>';
				print '<td align="center">'. $operation->date_next.'</td>';
				print '<td align="center">'. $operation->km_next.'</td>';
				print '<td align="center">'. (!empty($operation->on_time)?dolGetBadge($langs->trans('VehiculeOperationOnTime'), '', 'danger'):'').'</td>';
				print '<td align="center">';
				if (!empty($operation->or_next)) {
					$operationorder = new OperationOrder($object->db);
					$res = $operationorder->fetch($operation->or_next, false);
					if ($res<0) {
						setEventMessages($operationorder->error, $operationorder->errors, 'errors');
					}
					print $operationorder->getNomUrl(0);
				}
				print '</td>';
				print '<td align="center">';
				print '<input class="button quatrevingtpercent" type="submit" name="saveOperation" value="' . $langs->trans("Save") . '">';
				print '</td>';
			} else {
				print '<td align="left">' . $operation->getName() . '</td>';
				print '<td align="center">' . (!empty($operation->km) ? price2num($operation->km) : '') . '</td>';
				print '<td align="center">' . (!empty($operation->delai_from_last_op) ? $operation->delai_from_last_op . ' ' . $langs->trans('Months') : '') . '</td>';
				print '<td align="center">';
				if (!empty($operation->date_done)) {
					print dol_print_date($operation->date_done, "%d/%m/%Y");
				}
				print '</td>';
				print '<td align="center">' . (!empty($operation->km_done) ? $operation->km_done : '') . '</td>';

				print '<td align="center">';
				if (!empty($operation->date_next)) {
					print dol_print_date($operation->date_next, "%d/%m/%Y");
				}
				print '</td>';
				print '<td align="center">'. $operation->km_next.'</td>';
				print '<td align="center">'.  (!empty($operation->on_time)?dolGetBadge($langs->trans('VehiculeOperationOnTime'), '', 'danger'):'').'</td>';
				print '<td align="center">';
				if (!empty($operation->or_next)) {
					$operationorder = new OperationOrder($object->db);
					$res = $operationorder->fetch($operation->or_next, false);
					if ($res<0) {
						setEventMessages($operationorder->error, $operationorder->errors, 'errors');
					}
					print $operationorder->getNomUrl(0);
				}
				print '</td>';
				print '<td align="center">';
				print '<a href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=editOperation&ope_id=' . $operation->id . '">' . img_edit() . '</a>';
				print '<a href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=delOperation&ope_id=' . $operation->id . '">' . img_delete() . '</a>';
				print '</td>';
			}
			print '</tr>';
		}
	}

	if (GETPOST('action', 'alpha') !== 'editOperation'
		&& GETPOST('action', 'alpha') !== 'delOperation') {
		// new line
		print '<tr>';

		print '<td align="center">';
		print $form->select_produits(GETPOST('productid'), 'productid', 1, 0, 0, 1, 2, '', 0);
		print '</td>';

		print '<td align="center">';
		print '<input class="quatrevingtpercent" type="number" name="km" id="km" step="1" value="' . GETPOST('km') . '">';
		print '</td>';

		print '<td align="center">';
		print '<input class="soixantepercent" type="number" name="delay" id="delay" step="1" value="' . GETPOST('delay') . '">&nbsp;' . $langs->trans('Months');
		print '</td>';
		print '<td align="center">';
		$date_done=dol_mktime(0, 0, 0,
				GETPOST('date_donemonth', 'int'),
				GETPOST('date_doneday', 'int'),
				GETPOST('date_doneyear', 'int'));
		print $form->selectDate($date_done, 'date_done');
		print '</td>';
		print '<td align="center"><input class="quatrevingtpercent" type="number" name="km_done" id="km" step="1" value="' . GETPOST('km_done', 'int') . '"></td>';

		print '<td align="center" colspan="5">';
		print '<input class="button quatrevingtpercent" type="submit" name="addOperation" value="' . $langs->trans("Add") . '">';
		print '</td>';

		print '</tr>';
	}

	print '</table>';

	print '</form>';
	?>
	<script>
		$("#search_productid").removeClass("minwidth100");
		$("#search_productid").addClass("quatrevingtpercent");
	</script>
	<?php
}

/**
 * @param doliFleetVehicule $object
 */
function printVehiculeOperationsNp($object)
{
	global $langs, $form;
	dol_include_once('operationorder/class/operationorder.class.php');

	print load_fiche_titre($langs->trans('VehiculeOperationsNp'), '', '');

	print '<form id="vehiculeOperationsNpForm" method="POST" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '">';
	print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
	print '<input type="hidden" name="action" value="addVehiculeOperationNp">';
	print '<input type="hidden" name="id" value="' . $object->id . '">';

	print '<table class="border" width="100%">' . "\n";
	print '<tr class="liste_titre">';
	print '<td align="center">' . $langs->trans('VehiculeOperationNp') . '</td>';
	print '<td align="center"></td>';
	print '</tr>';

	$res = $object->getOperationsNp();
	if ($res < 0) {
		setEventMessages($object->error, $object->errors, 'errors');
	}
	if (empty($object->operations)) {
		print '<tr><td align="center" colspan="6">' . $langs->trans('NodoliFleet') . '</td></tr>';
	} else {
		foreach ($object->operations as $operation) {
			print '<tr>';
			print '<td align="left">' . $operation->getName() . '</td>';
			print '<td align="center">';
			print '<a href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=delOperationNp&openp_id=' . $operation->id . '">' . img_delete() . '</a>';
			print '</td>';
			print '</tr>';
		}
	}

	if (GETPOST('action', 'alpha') !== 'delOperationNp') {
		// new line
		print '<tr>';

		print '<td align="center">';
		print $form->select_produits(GETPOST('productid'), 'productid', 1, 0, 0, 1, 2, '', 0);
		print '</td>';

		print '<td align="center" colspan="2">';
		print '<input class="button quatrevingtpercent" type="submit" name="addOperationNp" value="' . $langs->trans("Add") . '">';
		print '</td>';

		print '</tr>';
	}

	print '</table>';

	print '</form>';
	?>
	<script>
		$("#search_productid").removeClass("minwidth100");
		$("#search_productid").addClass("quatrevingtpercent");
	</script>
	<?php
}

function printBannerVehicleCard($vehicle)
{

	global $db, $langs;

	$linkback = '<a href="' . dol_buildpath('/dolifleet/vehicule_list.php', 1) . '?restore_lastsearch_values=1">' . $langs->trans('BackToList') . '</a>';

	$morehtmlref = '<div class="refidno">';
	if (!empty($vehicle->immatriculation)) $morehtmlref .= '<br>' . $langs->trans('immatriculation') . ': ' . $vehicle->immatriculation;

	// marque
	dol_include_once('/dolifleet/class/dictionaryVehiculeMark.class.php');
	$dict = new dictionaryVehiculeMark($db);
	$morehtmlref .= '<br>' . $langs->trans('vehiculeMark') . ': ' . $dict->getValueFromId($vehicle->fk_vehicule_mark);

	// type de véhicule
	dol_include_once('/dolifleet/class/dictionaryVehiculeType.class.php');
	$dict = new dictionaryVehiculeType($db);
	$morehtmlref .= '<br>' . $langs->trans('vehiculeType') . ': ' . $dict->getValueFromId($vehicle->fk_vehicule_type);

	// client
	$vehicle->fetch_thirdparty();
	$morehtmlref .= '<br>' . $langs->trans('ThirdParty') . ' : ' . $vehicle->thirdparty->getNomUrl(1, 'customer');

	$morehtmlref .= '</div>';

	$vehicle->ref = $vehicle->vin;
	dol_banner_tab($vehicle, 'vin', $linkback, 1, 'vin', 'ref', $morehtmlref, '', 0, '', '');
}


function getNbORVehicle($idvehicle)
{
	global $db;

	$sql = 'SELECT COUNT(o.rowid) as nb FROM ' . MAIN_DB_PREFIX . 'operationorder as o ';
	$sql .= ' WHERE o.fk_vehicule = ' . $idvehicle;
	$sql .= ' AND o.entity IN (' . getEntity('operationorder') . ')';
	$resql = $db->query($sql);

	if ($resql) {
		$obj = $db->fetch_object($resql);

		$nbOperationOrder = $obj->nb;

		return $nbOperationOrder;
	} else {
		return -1;
	}
}

/**
	 * @param       $method
	 * @param       $url
	 * @param false $data
	 * @param false $header
	 * @return array|false|int|mixed|object
	 */
function callAPI($method, $url, $data = false, $header = false)
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
	curl_setopt($curl, CURLOPT_USERPWD, getDolGlobalString("THEO_API_USER")  . ':' . getDolGlobalString("THEO_API_PASS") );

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
		curl_close($curl);
		return -1;
	}
}

/**
 * Prepare array of tabs for Vehicule Setup screen
 * @return    array                    Array of tabs
 */
function VhSetupPrepareHead(): array
{
	global $langs, $conf;

	$langs->load("operationorder@operationorder");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/dolifleet/param/vh_setup_marque.php", 1);
	$head[$h][1] = $langs->trans("DolifleetSetupMarque");
	$head[$h][2] = 'marque';
	$h++;

	$head[$h][0] = dol_buildpath("/dolifleet/param/vh_setup_type.php", 1);
	$head[$h][1] = $langs->trans("DolifleetSetupType");
	$head[$h][2] = 'type';
	$h++;

	$head[$h][0] = dol_buildpath("/dolifleet/param/vh_setup_typect.php", 1);
	$head[$h][1] = $langs->trans("DolifleetSetupTypeCt");
	$head[$h][2] = 'typect';
	$h++;

	$head[$h][0] = dol_buildpath("/dolifleet/param/vh_setup_pneu.php", 1);
	$head[$h][1] = $langs->trans("DolifleetSetupPneu");
	$head[$h][2] = 'pneu';
	$h++;

	complete_head_from_modules($conf, $langs,null, $head, $h, 'operationordersetup@operationorder');

	complete_head_from_modules($conf, $langs, null, $head, $h, 'operationordersetup@operationorder', 'remove');

	return $head;
}
