<?php

/**
 * This file is a simple hook installer/uninstaller.
 */

// If we have found SSI.php and we are outside of ElkArte, then we are running standalone.
if (file_exists(dirname(__FILE__) . '/SSI.php') && !defined('ELK'))
{
	require_once(dirname(__FILE__) . '/SSI.php');
}
// If we are outside ElkArte and can't find SSI.php, then throw an error
elseif (!defined('ELK'))
{
	die('<b>Error:</b> Please verify you put this file in the same place as ElkArte\'s SSI.php.');
}

// Define the hooks
$hook_functions = array(
	'integrate_profile_areas' => array('function' => 'ipa_extauth', 'file' => 'SOURCEDIR/Extauth.integration.php'),
	'integrate_sa_manage_registrations' => array('function' => 'ismr_extauth', 'file' => 'SOURCEDIR/Extauth.integration.php'),
	'integrate_admin_areas' => array('function' => 'iaa_extauth', 'file' => 'SOURCEDIR/Extauth.integration.php'),
	'integrate_action_auth_before' => array('function' => 'iaab_extauth', 'file' => 'SOURCEDIR/Extauth.integration.php'),
	'integrate_action_register_before' => array('function' => 'iarb_extauth', 'file' => 'SOURCEDIR/Extauth.integration.php'),
	'integrate_delete_members' => array('function' => 'idm_extauth', 'file' => 'SOURCEDIR/Extauth.integration.php'),
	'integrate_load_theme' => array('function' => 'ilt_extauth', 'file' => 'SOURCEDIR/Extauth.integration.php'),
);

// Adding or removing them?
if (!empty($context['uninstalling']))
{
	$call = 'remove_integration_function';
}
else
{
	$call = 'add_integration_function';
}

// Do the deed
foreach ($hook_functions as $hook => $definition)
{
	if (!isset($definition['file']))
	{
		$definition['file'] = '';
	}

	$call($hook, $definition['function'], $definition['file']);
}

if (ELK === 'SSI')
{
	echo 'Congratulations! You have successfully installed the hooks for this Addon';
}
