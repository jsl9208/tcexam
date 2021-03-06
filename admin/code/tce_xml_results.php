<?php
//============================================================+
// File name   : tce_xml_results.php
// Begin       : 2008-06-06
// Last Update : 2013-09-05
//
// Description : Export all users' results in XML or JSON format.
//
// Author: Nicola Asuni
//
// (c) Copyright:
//               Nicola Asuni
//               Tecnick.com LTD
//               www.tecnick.com
//               info@tecnick.com
//
// License:
//    Copyright (C) 2004-2013  Nicola Asuni - Tecnick.com LTD
//
//    This program is free software: you can redistribute it and/or modify
//    it under the terms of the GNU Affero General Public License as
//    published by the Free Software Foundation, either version 3 of the
//    License, or (at your option) any later version.
//
//    This program is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU Affero General Public License for more details.
//
//    You should have received a copy of the GNU Affero General Public License
//    along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
//    Additionally, you can't remove, move or hide the original TCExam logo,
//    copyrights statements and links to Tecnick.com and TCExam websites.
//
//    See LICENSE.TXT file for more information.
//============================================================+

/**
 * @file
 * Export all users' results in XML or JSON format.
 * @package com.tecnick.tcexam.admin
 * @author Nicola Asuni
 * @since 2004-06-11
 */

/**
 */

require_once('../config/tce_config.php');
$pagelevel = K_AUTH_ADMIN_RESULTS;
require_once('../../shared/code/tce_authorization.php');
require_once('../../shared/code/tce_functions_test_stats.php');

if (isset($_REQUEST['test_id']) AND ($_REQUEST['test_id'] > 0)) {
	$test_id = intval($_REQUEST['test_id']);
	if (!F_isAuthorizedUser(K_TABLE_TESTS, 'test_id', $test_id, 'test_user_id')) {
		exit;
	}
} else {
	$test_id = 0;
}
if (isset($_REQUEST['group_id']) AND ($_REQUEST['group_id'] > 0)) {
	$group_id = intval($_REQUEST['group_id']);
} else {
	$group_id = 0;
}
if (isset($_REQUEST['user_id'])) {
	$user_id = intval($_REQUEST['user_id']);
} else {
	$user_id = 0;
}
if (isset($_REQUEST['startdate'])) {
	$startdate = $_REQUEST['startdate'];
	$startdate_time = strtotime($startdate);
	$startdate = date(K_TIMESTAMP_FORMAT, $startdate_time);
} else {
	$startdate = 0;
}
if (isset($_REQUEST['enddate'])) {
	$enddate = $_REQUEST['enddate'];
	$enddate_time = strtotime($enddate);
	$enddate = date(K_TIMESTAMP_FORMAT, $enddate_time);
} else {
	$enddate = 0;
}

$output_format = isset($_REQUEST['format']) ? strtoupper($_REQUEST['format']) : 'XML';
$out_filename = 'tcexam_results_'.date('YmdHis').'_test_'.$test_id;
$xml = F_xml_export_results($test_id, $group_id, $user_id, $startdate, $enddate);

switch($output_format) {
	case 'JSON': {
		header('Content-Description: JSON File Transfer');
		header('Cache-Control: public, must-revalidate, max-age=0'); // HTTP/1.1
		header('Pragma: public');
		header('Expires: Thu, 04 Jan 1973 00:00:00 GMT'); // Date in the past
		header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
		// force download dialog
		header('Content-Type: application/force-download');
		header('Content-Type: application/octet-stream', false);
		header('Content-Type: application/download', false);
		header('Content-Type: application/json', false);
		// use the Content-Disposition header to supply a recommended filename
		header('Content-Disposition: attachment; filename='.$out_filename.'.json;');
		header('Content-Transfer-Encoding: binary');
		$xmlobj = new SimpleXMLElement($xml);
		echo json_encode($xmlobj);
		break;
	}
	case 'XML':
	default: {
		header('Content-Description: XML File Transfer');
		header('Cache-Control: public, must-revalidate, max-age=0'); // HTTP/1.1
		header('Pragma: public');
		header('Expires: Thu, 04 Jan 1973 00:00:00 GMT'); // Date in the past
		header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
		// force download dialog
		header('Content-Type: application/force-download');
		header('Content-Type: application/octet-stream', false);
		header('Content-Type: application/download', false);
		header('Content-Type: application/xml', false);
		// use the Content-Disposition header to supply a recommended filename
		header('Content-Disposition: attachment; filename='.$out_filename.'.xml;');
		header('Content-Transfer-Encoding: binary');
		echo $xml;
		break;
	}
}

/**
 * Export results in XML format.
 * @param $test_id (int) test ID.
 * @param $group_id (int) group ID - if greater than zero, filter stats for the specified user group.
 * @param $user_id (int) user ID - if greater than zero, filter stats for the specified user.
 * @param $startdate (int) start date ID - if greater than zero, filter stats for the specified starting date
 * @param $enddate (int) end date ID - if greater than zero, filter stats for the specified ending date
 * @author Nicola Asuni
 * @return XML data
 */
function F_xml_export_results($test_id, $group_id=0, $user_id=0, $startdate=0, $enddate=0) {
	global $l, $db;
	require_once('../config/tce_config.php');
	
	$xml = ''; // XML data to be returned

	$xml .= '<'.'?xml version="1.0" encoding="UTF-8" ?'.'>'.K_NEWLINE;
	$xml .= '<tcexamresults version="'.K_TCEXAM_VERSION.'">'.K_NEWLINE;
	$xml .=  K_TAB.'<header';
	$xml .= ' lang="'.K_USER_LANG.'"';
	$xml .= ' date="'.date(K_TIMESTAMP_FORMAT).'">'.K_NEWLINE;
	$xml .= K_TAB.K_TAB.'<test_id>'.$test_id.'</test_id>'.K_NEWLINE;
	$xml .= K_TAB.K_TAB.'<group_id>'.$group_id.'</group_id>'.K_NEWLINE;
	$xml .= K_TAB.K_TAB.'<user_id>'.$user_id.'</user_id>'.K_NEWLINE;
	$xml .= K_TAB.K_TAB.'<startdate>'.$startdate.'</startdate>'.K_NEWLINE;
	$xml .= K_TAB.K_TAB.'<enddate>'.$enddate.'</enddate>'.K_NEWLINE;
	$xml .= K_TAB.'</header>'.K_NEWLINE;
	$xml .= K_TAB.'<body>'.K_NEWLINE;

	$data = F_getAllUsersTestStat($test_id, $group_id, $user_id, $startdate, $enddate);
	$xml .= getDataXML($data);

	$xml .= K_TAB.'</body>'.K_NEWLINE;
	$xml .= '</tcexamresults>'.K_NEWLINE;
	
	return $xml;
}

//============================================================+
// END OF FILE
//============================================================+
