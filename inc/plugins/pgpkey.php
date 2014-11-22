<?php
 /* Copyright (c) 2014 by MeshCollider.

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>. */

// Disallow direct access to this file for security reasons
if (!defined("IN_MYBB")) {
	die("Direct initialization of this file is not allowed.");
}

// Hooks
$plugins->add_hook("member_profile_start", "pgpkey_member_profile_start");
$plugins->add_hook("usercp_start", "phpkey_usercp_start");

function pgpkey_info() {
	return array(
		"name"			=> "User PGP Keys",
		"description"	=> "Allow users to add a PGP key to their profile",
		"website"		=> "",
		"author"		=> "MeshCollider",
		"authorsite"	=> "https://github.com/MeshCollider",
		"version"		=> "v1.0.0",
		"guid" 			=> "",
		"compatibility" => "*"
	);
}

function pgpkey_install() {
	global $mybb, $db, $cache;
	
  	if (!$db->table_exists("pgpkeys")) {
		$db->query("CREATE TABLE IF NOT EXISTS `" . TABLE_PREFIX . "pgpkeys` (
			`id` smallint(10) unsigned NOT NULL AUTO_INCREMENT,
			`uid` varchar(10) NOT NULL DEFAULT '',
			`pgpkey` varchar(4096) NOT NULL DEFAULT '',
			`fingerprint` varchar(16) NOT NULL,
			PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;");
	}
}

function pgpkey_is_installed() {
	global $db;
	
  	return $db->table_exists("pgpkeys");
}

function pgpkey_uninstall() {
	global $mybb, $db;
	
	if ($db->table_exists("pgpkeys")) {
		$db->query("DROP TABLE IF EXISTS `" . TABLE_PREFIX . "pgpkeys`");
	}
}

function pgpkey_activate() {
	global $db;
	
	require_once MYBB_ROOT . '/inc/adminfunctions_templates.php';

	$db->insert_query("templates", array(
		"tid"		=> NULL,
		"title"		=> "PGPKey View Page",
		"template"	=> '<html>
	<head>
		<title>PGPKey View</title>
		{$headerinclude}
	</head>
	<body>
		{$header}
		<h2>PGP Public Key for: {$username}</h2>
		{$pgpkey}
		<br />
		{$footer}
	</body>
</html>',
		"sid"		=> "-1"));
		
	$db->insert_query("templates", array(
		"tid"		=> NULL,
		"title"		=> "PGPKey View Key",
		"template"	=> '<p>{$pgpkey}</p><p>{$fingerprint}</p>',
		"sid"		=> "-1"));
				
	$db->insert_query("templates", array(
		"tid"		=> NULL,
		"title"		=> "PGPKey Default Page",
		"template"	=> '<html>
	<head>
		<title>PGPKey</title>
		{$headerinclude}
	</head>
	<body>
		{$header}
		<h2>PGP Public Key</h2>
		<br />
		<a href="pgp.php?action=view">View your PGP Public Key</a><br />
		<a href="pgp.php?action=addkey">Add or Change your PGP Public Key</a>
		<br />
		{$footer}
	</body>
</html>',
		"sid"		=> "-1"));
		
	$db->insert_query("templates", array(
		"tid"		=> NULL,
		"title"		=> "PGPKey Add Page",
		"template"	=> '<html>
	<head>
		<title>Add PGP Key</title>
		{$headerinclude}
	</head>
	<body>
		{$header}
		<h2>Add a PGP Public Key to your profile</h2>
		<form method="post" action="pgp.php?action=addkey">
			<table cellspacing="0" cellpadding="5">
				<tr>
					<td colspan=2>{$alert}</td>
				</tr>
				<tr>
					<td>Key:</td><td><input type="text" class="textbox" size="40" name="pgp_key" value="{$currentpgpkey}" /></td>
				</tr>
				<tr>
					<td>fingerprint:</td><td><input type="text" class="textbox" size="40" name="pgp_fingerprint" value="{$currentfingerprint}" /></td>
				</tr>
				<tr>
					<td><input class="button" type="submit" value="Add Key" /></td>
				</tr>
			</table>
		</form>
		{$footer}
	</body>
</html>',
		"sid"		=> "-1"));
	
	$db->insert_query("templates", array(
		"tid"		=> NULL,
		"title"		=> "PGPKey Alert Good",
		"template"	=> '<div class="red_alert" style="background-color: #e8fcdc; border-color: #080; color: #080;">{$alert_text}</div>',
		"sid"		=> "-1"));
		
	$db->insert_query("templates", array(
		"tid"		=> NULL,
		"title"		=> "PGPKey Alert Bad",
		"template"	=> '<div class="red_alert">{$alert_text}</div>',
		"sid"		=> "-1"));
				
	find_replace_templatesets("member_profile", "#" . preg_quote('{$warning_level}') 	. "#", 	'{$warning_level}{$pgpkey}');
	find_replace_templatesets("usercp", 		"#" . preg_quote('{$referral_info}') 	. "#",	'{$pgpkey}{$referral_info}');
	find_replace_templatesets("header", 		"#" . preg_quote('{$menu_memberlist}') 	. "#",	'{$menu_memberlist}<li><a href="{$mybb->settings[\'bburl\']}/pgp.php" class="search">PGP Key</a></li>');
}

function pgpkey_deactivate() {
	//Called whenever a plugin is deactivated. This should essentially "hide" the plugin from view by removing templates/template changes etc. It should not, however, remove any information such as tables, fields etc - that should be handled by an _uninstall routine. When a plugin is uninstalled, this routine will also be called before _uninstall() if the plugin is active.
	
	global $db;
	
	include MYBB_ROOT."/inc/adminfunctions_templates.php";
	
	$db->delete_query("templates", "title LIKE 'PGPKey View Page'");
	$db->delete_query("templates", "title LIKE 'PGPKey View Key'");
	$db->delete_query("templates", "title LIKE 'PGPKey Default Page'");
	$db->delete_query("templates", "title LIKE 'PGPKey Add Page'");
	$db->delete_query("templates", "title LIKE 'PGPKey Alert Good'");
	$db->delete_query("templates", "title LIKE 'PGPKey Alert Bad'");
	
	find_replace_templatesets("member_profile", "#" . preg_quote('{$pgpkey}') 																. "#", "");
	find_replace_templatesets("usercp", 		"#" . preg_quote('{$pgpkey}') 																			. "#", "");
	find_replace_templatesets("header", 		"#" . preg_quote('<li><a href="{$mybb->settings[\'bburl\']}/pgp.php" class="search">PGP Key</a></li>') 	. "#", "");
}

/*
// Usergroups Tab
$plugins->add_hook('admin_user_groups_edit_graph_tabs', 'mystatus_usergroup_perms_tab');
function mystatus_usergroup_perms_tab(&$tabs)
{
	global $lang;
	$lang->load('mystatus');
	// The MyStatus tab within the usergroup modification page
	$tabs['mystatus'] = $lang->group_mystatus;
}

// Usergroups Tab Area
$plugins->add_hook('admin_user_groups_edit_graph', 'mystatus_usergroup_perms');
function mystatus_usergroup_perms()
{
	global $lang, $form, $mybb;
	$lang->load('mystatus');
	// The container for the actual MyStatus permissions
	echo '<div id="tab_mystatus">';
	$form_container = new FormContainer($lang->group_mystatus);
	$form_container->output_row($lang->mystatus_can_use, "", $form->generate_yes_no_radio('mystatus_can_use', $mybb->input['mystatus_can_use'], true), 'mystatus_can_use');
	$form_container->output_row($lang->mystatus_can_moderate, "", $form->generate_yes_no_radio('mystatus_can_moderate', $mybb->input['mystatus_can_moderate'], true), 'mystatus_can_moderate');
	$form_container->output_row($lang->mystatus_can_delete_own, "", $form->generate_yes_no_radio('mystatus_can_delete_own', $mybb->input['mystatus_can_delete_own'], true), 'mystatus_can_delete_own');
	$form_container->end();
	echo '</div>';
}

// Usergroup Permissions Save
$plugins->add_hook('admin_user_groups_edit_commit', 'mystatus_usergroup_perms_save');
function mystatus_usergroup_perms_save()
{
	global $updated_group, $mybb;
	// Get the new information and place it within the $updated_group variable to insert into the database
	$updated_group['mystatus_can_use'] = intval($mybb->input['mystatus_can_use']);
	$updated_group['mystatus_can_moderate'] = intval($mybb->input['mystatus_can_moderate']);
	$updated_group['mystatus_can_delete_own'] = intval($mybb->input['mystatus_can_delete_own']);
}
*/

function pgpkey_member_profile_start() {
	global $db, $mybb, $pgpkey;

	$query = $db->simple_select("pgpkeys", "fingerprint", "uid = '" . intval($mybb->input['uid']) . "'");
	
	if ($query->num_rows == 1) {
		$returndata = $db->fetch_array($query);
		$fingerprint = $returndata['fingerprint'];
		$uid = intval($mybb->input['uid']);
		
		$pgpkey = "<tr>
	<td class='trow1'>
		<strong>PGP Key fingerprint:</strong>
	</td>
	<td class='trow1'>
		<a target='_blank' href='" . $mybb->settings['bburl'] . "/pgp.php?action=view&uid=" . $uid . "'>" . $fingerprint . "</a>
	</td>
</tr>";
	}
}

function pgpkey_usercp_start() {
	global $db, $pgpkey, $mybb;
	
	$query = $db->simple_select("pgpkeys", "fingerprint", "uid = '" . intval($mybb->input['uid']) . "'");
	if ($query->num_rows == 1) {
		$returndata = $db->fetch_array($query);
		$fingerprint = $returndata['fingerprint'];	
		
		$pgpkey = "<strong>PGP Key fingerprint: </strong><a target='_blank' href='" . $mybb->settings['bburl'] . "/pgp.php?action=view'>" . $fingerprint . "</a><br />";
	} else {
		$pgpkey = "";
	}
}
