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


/**
 * \file       htdocs/webportal/class/html.formlistwebportal.class.php
 * \ingroup    webportal
 * \brief      File of class with all html predefined components for WebPortal
 */
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/webportal/class/html.formwebportal.class.php';
require_once DOL_DOCUMENT_ROOT . '/webportal/class/html.formlistwebportal.class.php';

/**
 *    Class to manage generation of HTML components
 *    Only common components for WebPortal must be here.
 *
 */
class DoliFleetFormListWebPortal extends FormListWebPortal
{

	/**
	 * Init
	 *
	 * @param	string		$elementEn		Element (english) : "propal", "order", "invoice"
	 * @return	void
	 */
	public function init($elementEn)
	{
		// load module libraries
		dol_include_once('/dolifleet/class/webportal' . $elementEn . '.class.php');

		// Initialize a technical objects
		$objectclass = 'WebPortal' . ucfirst($elementEn);
		$object = new $objectclass($this->db);

		// set form list
		$this->action = GETPOST('action', 'aZ09');
		$this->object = $object;
		$this->limit = GETPOSTISSET('limit') ? GETPOSTINT('limit') : -1;
		$this->sortfield = GETPOST('sortfield', 'aZ09comma');
		$this->sortorder = GETPOST('sortorder', 'aZ09comma');
		$this->page = GETPOSTISSET('page') ? GETPOSTINT('page') : 1;
		$this->titleKey = $objectclass . 'ListTitle';

		// Initialize array of search criteria
		//$search_all = GETPOST('search_all', 'alphanohtml');
		$search = array();
		foreach ($object->fields as $key => $val) {
			if (GETPOST('search_' . $key, 'alpha') !== '') {
				$search[$key] = GETPOST('search_' . $key, 'alpha');
			}
			if (preg_match('/^(date|timestamp|datetime)/', $val['type'])) {
				$search[$key . '_dtstartmonth'] = GETPOSTINT('search_' . $key . '_dtstartmonth');
				$search[$key . '_dtstartday'] = GETPOSTINT('search_' . $key . '_dtstartday');
				$search[$key . '_dtstartyear'] = GETPOSTINT('search_' . $key . '_dtstartyear');
				$search[$key . '_dtstart'] = dol_mktime(
					23, 59, 59,
					$search[$key . '_dtstartmonth'],
					$search[$key . '_dtstartday'],
					$search[$key . '_dtstartyear']
				);
				$search[$key . '_dtendmonth'] = GETPOSTINT('search_' . $key . '_dtendmonth');
				$search[$key . '_dtendday'] = GETPOSTINT('search_' . $key . '_dtendday');
				$search[$key . '_dtendyear'] = GETPOSTINT('search_' . $key . '_dtendyear');
				$search[$key . '_dtend'] = dol_mktime(
					23, 59, 59,
					$search[$key . '_dtendmonth'],
					$search[$key . '_dtendday'],
					$search[$key . '_dtendyear']
				);
			}
		}
		$this->search = $search;

		// List of fields to search into when doing a "search in all"
		//$fieldstosearchall = array();
		// Definition of array of fields for columns
		$arrayfields = array();
		foreach ($object->fields as $key => $val) {
			// If $val['visible']==0, then we never show the field
			if (!empty($val['visible'])) {
				$visible = (int) dol_eval((string) $val['visible'], 1);
				$arrayfields['t.' . $key] = array(
					'label'	   => $val['label'],
					'checked'  => (($visible < 0) ? 0 : 1),
					'enabled'  => (int) (abs($visible) != 3 && (bool) dol_eval($val['enabled'], 1)),
					'position' => $val['position'],
					'help'	   => isset($val['help']) ? $val['help'] : ''
				);
			}
		}
		$object->fields = dol_sort_array($object->fields, 'position');
		//$arrayfields['anotherfield'] = array('type'=>'integer', 'label'=>'AnotherField', 'checked'=>1, 'enabled'=>1, 'position'=>90, 'csslist'=>'right');
		$arrayfields = dol_sort_array($arrayfields, 'position');

		$this->arrayfields = $arrayfields;
	}

	/**
	 * Do actions
	 *
	 * @return	void
	 */
	public function doActions()
	{
		$object = $this->object;
		$search = $this->search;

		// Purge search criteria
		// All tests are required to be compatible with all browsers
		if (
			GETPOST('button_removefilter_x', 'alpha')
			|| GETPOST('button_removefilter.x', 'alpha')
			|| GETPOST('button_removefilter', 'alpha')
		) {
			foreach ($object->fields as $key => $val) {
				$search[$key] = '';
				if (preg_match('/^(date|timestamp|datetime)/', $val['type'])) {
					//$search[$key . '_dtstart'] = '';
					//$search[$key . '_dtend'] = '';
					$search[$key . '_dtstartmonth'] = '';
					$search[$key . '_dtendmonth'] = '';
					$search[$key . '_dtstartday'] = '';
					$search[$key . '_dtendday'] = '';
					$search[$key . '_dtstartyear'] = '';
					$search[$key . '_dtendyear'] = '';
				}
			}
			$this->search = $search;
		}
	}

	/**
	 * List for an element in the page context
	 *
	 * @param	Context		$context		Context object
	 * @return	string		Html output
	 */
	public function elementList($context)
	{
		global $conf, $hookmanager, $langs;

		$html = '';
		$nbpages = 0;

		// initialize
		$action = $this->action;
		$object = $this->object;
		$limit = $this->limit;
		$page = $this->page;
		$sortfield = $this->sortfield;
		$sortorder = $this->sortorder;
		$titleKey = $this->titleKey;
		$contextpage = $this->contextpage;
		$search = $this->search;
		$arrayfields = $this->arrayfields;
		$elementEn = $object->element;

		if ($limit < 0) {
			$limit = $conf->liste_limit;
		}
		if ($page <= 0) {
			$page = 1;
		}
		$offset = $limit * ($page - 1);
		if (!$sortfield) {
			reset($object->fields); // Reset is required to avoid key() to return null.
			$sortfield = 't.' . key($object->fields); // Set here default search field. By default 1st field in definition.
		}
		if (!$sortorder) {
			$sortorder = 'DESC';
		}

		$socid = (int) $context->logged_thirdparty->id;

		// Build and execute select
		// --------------------------------------------------------------------
		$sql = "SELECT ";
		$sql .= $object->getFieldList('t');
		$sql .= ", t.entity as element_entity";

		$sql = preg_replace('/,\s*$/', '', $sql);

		$sqlfields = $sql; // $sql fields to remove for count total

		$sql .= " FROM " . $this->db->prefix() . $object->table_element . " as t";

		$sql .= " WHERE 1 = 1";
		// filter on logged third-party
		$sql .= " AND t.fk_soc = " . ((int) $socid);
		// discard record with status draft
		$sql .= $this->getSqlSearchFilters($object, $search);

		// Count total nb of records
		$nbtotalofrecords = 0;
		if (!getDolGlobalInt('MAIN_DISABLE_FULL_SCANLIST')) {
			$nbtotalofrecords = $this->getSqlNbTotalOfRecord($sql, $sqlfields, $offset, $page);
		}

		// Complete request and execute it with limit
		$sql .= $this->db->order($sortfield, $sortorder);
		if ($limit) {
			$sql .= $this->db->plimit($limit, $offset);
		}

		$resql = $this->db->query($sql);
		if (!$resql) {
			dol_print_error($this->db);
			return '';
		}

		$num = $this->db->num_rows($resql);
		if ($limit > 0) {
			$nbpages = ceil($nbtotalofrecords / $limit);
		}
		if ($nbpages <= 0) {
			$nbpages = 1;
		}

		// make array[sort field => sort order] for this list
		$sortList = $this->getSortList($sortfield, $sortorder);

		$param = $this->getParams($contextpage, $limit, $search);

		$url_file = $context->getControllerUrl($context->controller);
		$html .= '<form method="POST" id="searchFormList" action="' . $url_file . '">' . "\n";
		$html .= $context->getFormToken();
		$html .= '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
		$html .= '<input type="hidden" name="action" value="list">';
		$html .= '<input type="hidden" name="sortfield" value="' . $sortfield . '">';
		$html .= '<input type="hidden" name="sortorder" value="' . $sortorder . '">';
		$html .= '<input type="hidden" name="page" value="' . $page . '">';
		$html .= '<input type="hidden" name="contextpage" value="' . $contextpage . '">';

		// pagination
		$pagination_param = $param . '&sortfield=' . $sortfield . '&sortorder=' . $sortorder;
		$html .= '<nav id="webportal-' . $elementEn . '-pagination">';
		$html .= '<ul>';
		$html .= '<li><strong>' . $langs->trans($titleKey) . '</strong> (' . $nbtotalofrecords . ')</li>';
		$html .= '</ul>';

		/* Generate pagination list */
		$html .= static::generatePageListNav($url_file . $pagination_param, $nbpages, $page);

		$html .= '</nav>';

		// table with search filters and column titles
		$html .= '<table id="webportal-' . $elementEn . '-list" responsive="scroll" role="grid">';

		$html .= '<thead>';

		// Fields title search
		// --------------------------------------------------------------------
		$html .= $this->printLineSearchInputs($object, $arrayfields, $search);

		$html .= '</tr>';

		$totalarray = array();
		$totalarray['nbfield'] = 0;

		// Fields title label
		// --------------------------------------------------------------------
		$html .= $this->printLineTitle($object, $totalarray, $arrayfields, $sortList, $url_file, $param);
		$html .= '</thead>';
		$html .= '<tbody>';

		// Loop on record
		// --------------------------------------------------------------------
		$i = 0;

		$totalarray = [
			'nbfield'	  => 0,
			'totalizable' => [],
		];

		$imaxinloop = ($limit ? min($num, $limit) : $num);
		while ($i < $imaxinloop) {
			$obj = $this->db->fetch_object($resql);
			if (empty($obj)) {
				break; // Should not happen
			}

			// Store properties in $object
			$object->setVarsFromFetchObj($obj);
			$html .= $this->printLineVehicule($obj, $object, $arrayfields, $totalarray, $i, $context);

			$i++;
		}

		// If no record found
		if ($num == 0) {
			$colspan = 1;
			foreach ($arrayfields as $val) {
				if (!empty($val['checked'])) {
					$colspan++;
				}
			}
			$html .= '
				<tr>
					<td colspan="' . $colspan . '">
						<span class="opacitymedium">' . $langs->trans("NoRecordFound") . '</span>
					</td>
				</tr>';
		}

		$html .= '</tbody>';

		$this->db->free($resql);

		$html .= '</table>';

		$html .= '</form>';

		return $html;
	}

	public function getSqlNbTotalOfRecord($sql, $sqlfields, $offset, &$page)
	{
		/* The fast and low memory method to get and count full list converts the sql into a sql count */
		$sqlforcount = preg_replace(
			'/^' . preg_quote($sqlfields, '/') . '/',
			'SELECT COUNT(*) as nbtotalofrecords',
			$sql
		);

		$sqlforcount = preg_replace('/GROUP BY .*$/', '', $sqlforcount);
		$resql = $this->db->query($sqlforcount);

		if ($resql) {
			$objforcount = $this->db->fetch_object($resql);
			$nbtotalofrecords = (int) $objforcount->nbtotalofrecords;
		} else {
			dol_print_error($this->db);
		}

		// if total resultset is smaller than the paging size (filtering), goto and load page 1
		if ($offset > $nbtotalofrecords) {
			$page = 1;
			$offset = 0;
		}

		$this->db->free($resql);
		return $nbtotalofrecords;
	}

	public function getSqlSearchFilters($object, $search)
	{
		$sql = '';

		foreach ($search as $key => $val) {
			if (array_key_exists($key, $object->fields)) {
				if (($key == 'status') && $search[$key] == -1) {
					continue;
				}
				$mode_search = (($object->isInt($object->fields[$key]) || $object->isFloat($object->fields[$key])) ? 1 : 0);
				if (
					(strpos($object->fields[$key]['type'], 'integer:') === 0)
					|| (strpos($object->fields[$key]['type'], 'sellist:') === 0)
					|| !empty($object->fields[$key]['arrayofkeyval'])
				) {
					if (
						$search[$key] == "-1"
						|| (
							$search[$key] === '0'
							&& (
								empty($object->fields[$key]['arrayofkeyval'])
								|| !array_key_exists('0', $object->fields[$key]['arrayofkeyval'])
							)
						)
					) {
						$search[$key] = '';
					}
					$mode_search = 2;
				}

				if ($search[$key] != '') {
					$sql .= natural_search(
						"t." . $this->db->escape($key),
						$search[$key],
						(($key == 'status') ? ($search[$key] < 0 ? 1 : 2) : $mode_search)
					);
				}
			} elseif (preg_match('/(_dtstart|_dtend)$/', $key) && $search[$key] != '') {
				$columnName = preg_replace('/(_dtstart|_dtend)$/', '', $key);
				if (preg_match('/^(date|timestamp|datetime)/', $object->fields[$columnName]['type'])) {
					if (preg_match('/_dtstart$/', $key)) {
						$sql .= " AND t." . $this->db->escape($columnName) . " >= '" . $this->db->idate($search[$key]) . "'";
					}
					if (preg_match('/_dtend$/', $key)) {
						$sql .= " AND t." . $this->db->escape($columnName) . " <= '" . $this->db->idate($search[$key]) . "'";
					}
				}
			}
		}
		return $sql;
	}

	public function getParams($contextpage, $limit, $search)
	{
		$param = '&contextpage=' . urlencode($contextpage);
		$param .= '&limit=' . $limit;
		foreach ($search as $key => $val) {
			if (is_array($search[$key])) {
				foreach ($search[$key] as $skey) {
					if ($skey != '') {
						$param .= '&search_' . $key . '[]=' . urlencode($skey);
					}
				}
			} elseif (preg_match('/(_dtstart|_dtend)$/', $key) && !empty($val)) {
				$param .= '&search_' . $key . 'month=' . (GETPOSTINT('search_' . $key . 'month'));
				$param .= '&search_' . $key . 'day=' . (GETPOSTINT('search_' . $key . 'day'));
				$param .= '&search_' . $key . 'year=' . (GETPOSTINT('search_' . $key . 'year'));
			} elseif ($search[$key] != '') {
				$param .= '&search_' . $key . '=' . urlencode($search[$key]);
			}
		}
		return $param;
	}

	public function getSortList($sortfield, $sortorder)
	{
		$sortList = array();
		$sortFieldList = explode(",", $sortfield);
		$sortOrderList = explode(",", $sortorder);
		$sortFieldIndex = 0;
		if (!empty($sortFieldList)) {
			foreach ($sortFieldList as $sortField) {
				if (isset($sortOrderList[$sortFieldIndex])) {
					$sortList[$sortField] = $sortOrderList[$sortFieldIndex];
				}
				$sortFieldIndex++;
			}
		}
		return $sortList;
	}

	public function printLineTitle($object, &$totalarray, &$arrayfields, &$sortList, $url_file, $param)
	{
		global $langs;
		$html = '<tr>';

		// Action column
		$html .= '<th  data-col="row-checkbox"  ></th>';
		$totalarray['nbfield']++;

		foreach ($object->fields as $key => $val) {
			$prefix = 't.';
			$tableKey = $prefix . $key;
			if (!empty($arrayfields[$tableKey]['checked'])) {
				$tableOrder = '';
				if (array_key_exists($key, $sortList)) {
					$tableOrder = strtolower($sortList[$key]);
				}
				$url_param = $url_file . '&sortfield=' . $key . '&sortorder=' . ($tableOrder == 'desc' ? 'asc' : 'desc') . $param;
				$html .= '<th data-col="' . dol_escape_htmltag($key) . '"  scope="col"' . ($tableOrder != '' ? ' table-order="' . $tableOrder . '"'
						: '') . '>';
				$html .= '<a href="' . $url_param . '">';
				$html .= $langs->trans($arrayfields[$prefix . $key]['label']);
				$html .= '</a>';
				$html .= '</th>';
				$totalarray['nbfield']++;
			}
		}

		$html .= '</tr>';

		$html .= '</thead>';
		return $html;
	}

	public function printLineSearchInputs($object, $arrayfields, $search)
	{
		global $langs;
		$html = '<tr role="search-row">';
		$html .= '<td data-col="row-checkbox" >';
		$html .= '	<button class="btn-filter-icon btn-search-filters-icon" type="submit" name="button_search_x" value="x" aria-label="' . dol_escape_htmltag($langs->trans('Search')) . '" ></button>';
		$html .= '	<button class="btn-filter-icon btn-remove-search-filters-icon" type="submit" name="button_removefilter_x" value="x" aria-label="' . dol_escape_htmltag($langs->trans('RemoveSearchFilters')) . '"></button>';
		$html .= '</td>';
		// }

		foreach ($object->fields as $key => $val) {
			$prefix = 't.';
			if (empty($arrayfields[$prefix . $key]['checked'])) {
				continue;
			}

			$search_val = (isset($search[$key]) ? $search[$key] : '');
			$html .= '<td data-label="' . $arrayfields[$prefix . $key]['label'] . '" data-col="' . dol_escape_htmltag($key) . '" >';
			if (!empty($val['arrayofkeyval']) && is_array($val['arrayofkeyval'])) {
				$html .= $this->form->selectarray(
					'search_' . $key, $val['arrayofkeyval'], $search_val, $val['notnull'], 0, 0, '', 1, 0, 0, '', ''
				);
			} elseif (preg_match('/^(date|timestamp|datetime)/', $val['type'])) {
				$postDateStart = dol_mktime(
					0, 0, 0,
					(int) $search[$key . '_dtstartmonth'],
					(int) $search[$key . '_dtstartday'],
					(int) $search[$key . '_dtstartyear']
				);
				$postDateEnd = dol_mktime(
					0, 0, 0,
					(int) $search[$key . '_dtendmonth'],
					(int) $search[$key . '_dtendday'],
					(int) $search[$key . '_dtendyear']
				);

				$html .= '<div class="grid width150">';
				$html .= $this->form->inputDate(
					'search_' . $key . '_dtstart',
					$postDateStart ? $postDateStart : '',
					$langs->trans('From')
				);
				$html .= '</div>';
				$html .= '<div class="grid width150">';
				$html .= $this->form->inputDate(
					'search_' . $key . '_dtend',
					$postDateEnd ? $postDateEnd : '',
					$langs->trans('to')
				);
				$html .= '</div>';
			} else {
				$html .= '<input type="text" name="search_' . $key . '" value="' . dol_escape_htmltag($search_val) . '">';
			}
			$html .= '</td>';
		}

		$html .= '</tr>';
		return $html;
	}

	public function printLineVehicule($obj, $object, $arrayfields, &$totalarray, $i, $context)
	{

		// Show line of result
		$html = '<tr data-rowid="' . $obj->rowid . '">';
		// if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		$html .= '<td class="nowraponall">';
		$html .= '</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
		// }
		foreach ($object->fields as $key => $val) {
			if (!empty($arrayfields['t.' . $key]['checked'])) {
				$html .= '<td class="nowraponall" data-label="' . $arrayfields['t.' . $key]['label'] . '">';
				if ($key == 'status' || $key == 'fk_statut') {
					$html .= $object->getLibStatut(5);
				} elseif ($key == 'vin') {
					$url_file = $context->getControllerUrl('vehiculecard', ['vh_id' => $obj->rowid]);
					$html .= '<a href="' . $url_file . '">' . $obj->vin . '</a>';
				} else {
					$html .= $this->form->showOutputFieldForObject($object, $val, $key, $obj->{$key}, '');
				}
				$html .= '</td>';

				if (!$i) {
					$totalarray['nbfield']++;
				}
				if (!empty($val['isameasure']) && $val['isameasure'] == 1) {
					if (!$i) {
						$totalarray['pos'][$totalarray['nbfield']] = 't.' . $key;
					}
					if (!isset($totalarray['val'])) {
						$totalarray['val'] = array();
					}
					if (!isset($totalarray['val']['t.' . $key])) {
						$totalarray['val']['t.' . $key] = 0;
					}
					$totalarray['val']['t.' . $key] += $obj->{$key};
				}
			}
		}

		$html .= '</tr>';
		return $html;
	}
}
