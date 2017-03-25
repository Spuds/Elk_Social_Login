<?php

/**
 * @package "ExternalAuth" External Authentication Addon for Elkarte
 * @author Spuds
 * @copyright (c) 2017 Spuds
 * @license No derivative works. No warranty, explicit or implicit, provided.
 * The Software is provided under an AS-IS basis, Licensor shall never, and without any limit,
 * be liable for any damage, cost, expense or any other payment incurred by Licensee as a result
 * of Software’s actions, failure, bugs and/or any other interaction.
 *
 * @version 1.0.0
 *
 * This addon is based on code from:
 * @author Antony Derham
 * @copyright 2014 Antony Derham
 *
 */

// If we have found SSI.php and we are outside of ELK, then we are running standalone.
if (file_exists(dirname(__FILE__) . '/SSI.php') && !defined('ELK'))
{
	require_once(dirname(__FILE__) . '/SSI.php');
}
// If we are outside ELK and can't find SSI.php, then throw an error
elseif (!defined('ELK'))
{
	die('<b>Error:</b> Cannot install - please verify you put this file in the same place as Elkarte\'s SSI.php.');
}

global $db_prefix, $db_package_log;

// Create the oauth2_authentications table
$dbtbl = db_table();
$dbtbl->db_create_table($db_prefix . 'oauth2_authentications',
	array(
		array(
			'name' => 'id_member',
			'type' => 'int',
			'size' => 10,
		),
		array(
			'name' => 'provider',
			'type' => 'varchar',
			'size' => 32,
		),
		array(
			'name' => 'provider_uid',
			'type' => 'varchar',
			'size' => 32,
		),
		array(
			'name' => 'username',
			'type' => 'varchar',
			'size' => 32,
		),
	),
	array(
		array(
			'name' => 'id_member',
			'type' => 'index',
			'columns' => array('id_member'),
		),
		array(
			'name' => 'provider',
			'type' => 'unique',
			'columns' => array('id_member', 'provider'),
		),
	),
	array(),
	'ignore');

$db_package_log = $dbtbl->package_log();
