<?php
/**
 * PGP Public Key plugin for MyBB
 * Copyright (c) 2014 by MeshCollider.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

define("IN_MYBB", 1);
define("THIS_SCRIPT", "pgp.php");

require_once "./global.php";

//check if Omnicoin plugin is enabled
$enabled_plugins = $cache->read("plugins");
if (!array_key_exists("pgpkey", $enabled_plugins['active'])) {
	die();
}

if (!$mybb->user['uid']) {
	error_no_permission();
}

if (isset($mybb->input['action'])) {
	if ($mybb->input['action'] == "view") {
		if (isset($mybb->input['uid'])) {
			$uid = $mybb->input['uid']; 
		} else {
			$uid = $mybb->user[uid];
		}
		$uid = intval(preg_replace("/[^0-9]/", "", $uid));
			
		// get the username corresponding to the UID passed to the page
		$grabuser = $db->simple_select("users", "username", "uid = '" . $uid . "'");
		$user = $db->fetch_array($grabuser);
		$username = $user['username'];
	
		//get all past addresses from table
		$query = $db->simple_select("pgpkeys", "pgpkey, fingerprint", "uid = '" . $uid . "'");
		$entries = "";
			
		if ($query->num_rows > 0) {
			$row = $db->fetch_array($query);
			$key = $row['pgpkey'];
			$fingerprint = $row['fingerprint'];
			$template = $templates->get("PGPKey View Key");
			eval("\$pgpkey .=\"" . $template . "\";");
		} else {
			$pgpkey = "No PGP Key available";
		}
		
		// grab our template
		$template = $templates->get("PGPKey View Page");
		eval("\$page=\"" . $template . "\";");
		output_page($page);
	} else if ($mybb->input['action'] == "addkey") {
		$alert = "";
		$uid = $mybb->user[uid];
		$currentpgpkey = "";
		$currentfingerprint = "";
		
		if (isset($mybb->input['pgp_key']) && isset($mybb->input['pgp_fingerprint'])) {
			$good_alert_template = $templates->get("PGPKey Alert Good");
			$bad_alert_template = $templates->get("PGPKey Alert Bad");
			
			//Whitelist address so user can't inject into DB or API calls
			$key = $db->escape_string(preg_replace("/[^A-Za-z0-9]/", "", $mybb->input['pgp_key']));
			$footprint = $db->escape_string(preg_replace("/[^A-Za-z0-9=+-\/]/", "", $mybb->input['pgp_fingerprint']));
					
			//Do some verification here
			$db->update_query("pgpkeys", array("pgpkey" => $key, "fingerprint" => $fingerprint), "uid = '" . $uid . "'");	
			$alert_text = "PGP key added successfully!";
			eval("\$alert=\"" . $good_alert_template . "\";");
		}
		
		$query = $db->simple_select("pgpkeys", "pgpkey, fingerprint", "uid='" . $mybb->user['uid'] . "'");
		if ($query->num_rows == 1) {
			$returndata = $db->fetch_array($query);
			$currentpgpkey = $returndata['pgpkey'];	
			$currentfingerprint = $returndata['fingerprint'];
		}
		
		$template = $templates->get("PGPKey Add Page");
		eval("\$page=\"" . $template . "\";");
		output_page($page);
	} else {
		$template = $templates->get("PGPKey Default Page");
		eval("\$page=\"" . $template . "\";");
		output_page($page);
	}
} else {
	$template = $templates->get("PGPKey Default Page");
	eval("\$page=\"" . $template . "\";");
	output_page($page);	
}
?>
