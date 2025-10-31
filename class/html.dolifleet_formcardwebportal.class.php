<?php

/* Copyright (C) 2023-2024 	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2023-2024	Lionel Vessiller		<lvessiller@easya.solutions>
 * Copyright (C) 2023-2024	Patrice Andreani		<pandreani@easya.solutions>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */


require_once DOL_DOCUMENT_ROOT . '/webportal/class/html.formcardwebportal.class.php';
dol_include_once('/dolifleet/class/vehicule.class.php');

/**
 *    Class to manage generation of HTML components
 *    Only common components for WebPortal must be here.
 *
 */
class VehiculeFormCardWebPortal extends FormCardWebPortal
{

	/**
	 * Init
	 *
	 * @param	string	$elementEn				Element (english) : "member" (for adherent), "partnership"
	 * @param	int		$id						[=0] ID element
	 * @param	int		$permissiontoread		[=0] Permission to read (0 : access forbidden by default)
	 * @param	int		$permissiontoadd		[=0] Permission to add (0 : access forbidden by default), used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
	 * @param	int		$permissiontodelete		[=0] Permission to delete (0 : access forbidden by default)
	 * @param	int		$permissionnote			[=0] Permission to note (0 : access forbidden by default)
	 * @param	int		$permissiondellink		[=0] Permission to delete links (0 : access forbidden by default)
	 * @return	void
	 */
	public function init(
		$elementEn, $id = 0, $permissiontoread = 0, $permissiontoadd = 0, $permissiontodelete = 0, $permissionnote = 0, $permissiondellink =
		0
	) {
		global $hookmanager, $langs;

		$elementEnUpper = strtoupper($elementEn);
		$objectclass = 'WebPortal' . ucfirst($elementEn);

		if ($id <= 0) {
			accessforbidden();
		}

		// load module libraries
		dol_include_once('/dolifleet/class/webportal' . $elementEn . '.class.php');

		// Load translation files required by the page
		$langs->loadLangs(array('website', 'other', 'companies', 'dolifleet@dolifleet'));

		// Get parameters
		//$id = $id > 0 ? $id : GETPOST('id', 'int');
		$ref = GETPOST('ref', 'alpha');
		$action = GETPOST('action', 'aZ09');
		$confirm = GETPOST('confirm', 'alpha');
		$cancel = GETPOST('cancel', 'aZ09');
		$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'webportal' . $elementEn . 'card'; // To manage different context of search
		$backtopage = GETPOST('backtopage', 'alpha');	 // if not set, a default page will be used
		$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha'); // if not set, $backtopage will be used
		$backtopagejsfields = GETPOST('backtopagejsfields', 'alpha');

		// Initialize a technical objects
		$object = new WebPortalVehicule($this->db);
		//$extrafields = new ExtraFields($db);
		$hookmanager->initHooks(array('webportal' . $elementEn . 'card', 'globalcard')); // Note that conf->hooks_modules contains array
		// Fetch optionals attributes and labels
		//$extrafields->fetch_name_optionals_label($object->table_element);
		//$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

		if (empty($id)) {
			accessforbidden();
		}

		$action = 'view';

		$retFetch = $object->fetchWebVehicule($id);

		if ($retFetch < 0) {
			accessforbidden();
		}

		if (empty($retFetch)) {
			accessforbidden();
		}


		// Security check (enable the most restrictive one)
		if (!isModEnabled('webportal')) {
			accessforbidden();
		}
		if (!$permissiontoread) {
			accessforbidden();
		}

		// set form card
		$this->action = $action;
		$this->backtopage = $backtopage;
		$this->backtopageforcancel = $backtopageforcancel;
		$this->backtopagejsfields = $backtopagejsfields;
		$this->cancel = $cancel;
		$this->elementEn = $elementEn;
		$this->id = (int) $id;
		$this->object = $object;
		$this->permissiontoread = $permissiontoread;
		$this->permissiontoadd = $permissiontoadd;
		$this->permissiontodelete = $permissiontodelete;
		$this->permissionnote = $permissionnote;
		$this->permissiondellink = $permissiondellink;
		$this->titleKey = $objectclass . 'CardTitle';
		$this->ref = $ref;
	}

	/**
	 * Card for an element in the page context
	 *
	 * @param	Context		$context	Context object
	 * @return	string		Html output
	 */
	public function elementCard($context)
	{
		global $hookmanager, $langs;

		$html = '<!-- elementCard -->';

		// initialize
		$action = $this->action;
		$backtopage = $this->backtopage;
		$backtopageforcancel = $this->backtopageforcancel;
		//$backtopagejsfields = $this->backtopagejsfields;
		//$elementEn = $this->elementEn;
		$id = $this->id;
		$object = $this->object;
		//$permissiontoread = $this->permissiontoread;
		$permissiontoadd = $this->permissiontoadd;
		$ref = $this->ref;
		$titleKey = $this->titleKey;
		$title = $langs->trans($titleKey);

		// Part to show record
		$html .= '<article>';

		$formconfirm = '';

		// Call Hook formConfirm
		$parameters = array('formConfirm' => $formconfirm);
		$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if (empty($reshook)) {
			$formconfirm .= $hookmanager->resPrint;
		} elseif ($reshook > 0) {
			$formconfirm = $hookmanager->resPrint;
		}

		// Print form confirm
		$html .= $formconfirm;

		// Object card
		// ------------------------------------------------------------
		$html .= $this->header($context);

		// Common attributes
		$keyforbreak = '';
		$html .= $this->bodyView($keyforbreak);

		// Other attributes. Fields from hook formObjectOptions and Extrafields.
		//include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';
		//$html .= $this->footer();
		$html .= '</article>';
		return $html;
	}

	/**
	 * Html for body (view mode)
	 * @param	string	$keyforbreak	[=''] Key for break left block
	 * @return	string	Html for body
	 */
	protected function bodyView($keyforbreak = '')
	{
		global $langs;

		$html = '';

		// initialize
		$object = $this->object;

		$object->fields = dol_sort_array($object->fields, 'position');

		// separate fields to show on the left and on the right
		$fieldShowList = array();
		foreach ($object->fields as $key => $val) {
			// discard if it's a hidden field on form
			if (abs($val['visible']) != 1 && abs($val['visible']) != 3 && abs($val['visible']) != 4 && abs($val['visible']) != 5) {
				continue;
			}

			if (array_key_exists('enabled', $val) && isset($val['enabled']) && !verifCond($val['enabled'])) {
				continue; // we don't want this field
			}

			if (!empty($val['showonheader'])) {
				continue; // already on header
			}

			$fieldShowList[$key] = $val;
		}

		$html .= '<div class="grid">';
		$html .= '<div class="card-left">';
		$keyforbreak = 'km_date';
		unset($object->fields['dfol']);
		unset($object->fields['fk_soc']);
		foreach ($object->fields as $key => $val) {
			if (!array_key_exists($key, $fieldShowList)) {
				continue; // not to show
			}

			$value = $object->$key;

			$html .= '<div class="grid field_' . $key . '">';

			$html .= '<div class="' . (empty($val['tdcss']) ? '' : $val['tdcss']) . ' fieldname_' . $key;
			$html .= '">';
			$labeltoshow = '';
			$labeltoshow .= '<strong>' . $langs->trans($val['label']) . '</strong>';
			$html .= $labeltoshow;
			$html .= '</div>';

			$html .= '<div class="valuefield fieldname_' . $key;
			if (!empty($val['cssview'])) {
				$html .= ' ' . $val['cssview'];
			}
			$html .= '">';
			if ($key == 'lang') {
				$langs->load('languages');
				$labellang = ($value ? $langs->trans('Language_' . $value) : '');
				//$html .= picto_from_langcode($value, 'class="paddingrightonly saturatemedium opacitylow"');
				$html .= $labellang;
			} else {
				$html .= $this->form->showOutputFieldForObject($object, $val, $key, $value, '', '', '', 0);
			}
			$html .= '</div>';

			$html .= '</div>';


			// fields on the right
			if ($key == $keyforbreak) {
				$html .= '</div>';
				$html .= '<div class="card-right">';
			}
		}
		$html .= '</div>';
		$html .= '</div><br><br>';


		return $html;
	}

	/**
	 * Html for header
	 *
	 * @param	Context	$context	Context object
	 * @return	string
	 */
	protected function header($context)
	{
		global $langs;

		$html = '';

		// initialize
		$object = $this->object;
		$addgendertxt = '';

		$html .= '
			<!-- html.formcardwebportal.class.php -->
			<header>
				<div class="header-card-left-block inline-block" style="width: 75%;">
					<div class="header-card-main-information inline-block valignmiddle">
						<div><strong>' . $langs->trans("ThirdParty") . ' : ' . dol_escape_htmltag($context->logged_thirdparty->ref) . '</strong></div>
					</div>
				</div>
				<div class="header-card-right-block inline-block" style="width: 24%;">';


		$html .= '</div>';
		// Right block - end

		$html .= '</header>';

		return $html;
	}

	public function getorlinkedHV($vehicule)
	{
		$sql = 'SELECT IF(fk_target = ' . $vehicule->id . ',fk_source,fk_target) as linked FROM ' . $this->db->prefix() . 'dolifleet_vehicule_link ';
		$sql .= 'WHERE (fk_source = ' . $vehicule->id . ' OR fk_target = ' . $vehicule->id . ') ORDER BY date_start DESC';
		$resql = $this->db->query($sql);
		if ($resql) {
			dol_syslog($this->db->lasterror(), 'LOG_ERR');
			return '';
		}

		$num = $this->db->num_rows($resql);
		if ($num <= 0) {
			return '';
		}

		$obj = $this->db->fetch_object($resql);
		$vh = new doliFleetVehicule($this->db);
		$ret = $vh->fetch($obj->linked);
		if ($ret < 0) {
			dol_syslog(implode(',', array_merge($vh->errors, [$vh->error])), 'LOG_ERR');
			return '';
		}

		if ($ret == 0) {
			return '';
		}
		return $vh->vin . ' - ' . $vh->immatriculation;
	}
}
