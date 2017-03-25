<?php

/**
 * @package "ExternalAuth" External Authentication Addon for Elkarte
 * @author Spuds
 * @copyright (c) 2017 Spuds
 * @license No derivative works. No warranty, explicit or implicit, provided.
 * The Software is provided under an AS-IS basis, Licensor shall never, and without any limit,
 * be liable for any damage, cost, expense or any other payment incurred by Licensee as a result
 * of Software’s actions, failure, bugs and/or any other interaction.
 * @version 1.0.0
 *
 * This addon is based on code from:
 * @author Antony Derham
 * @copyright 2014 Antony Derham
 */

if (!defined('ELK'))
{
	die('No access...');
}

/**
 * Retrieve a member's settings based on the provider and provider uid
 *
 * @param string $provider the provider they're using
 * @param string $uid the provider's unique identifier
 *
 * @return array the member settings
 */
function memberByExtUID($provider, $uid)
{
	$db = database();

	$result = $db->query('', '
		SELECT 
			passwd, mem.id_member, id_group, lngfile, is_activated, email_address, 
			additional_groups, member_name, password_salt
		FROM {db_prefix}members AS mem
			LEFT JOIN {db_prefix}oauth2_authentications AS ext ON mem.id_member = ext.id_member
		WHERE ext.provider = {string:provider}
			AND ext.provider_uid = {string:provider_uid}',
		array(
			'provider' => $provider,
			'provider_uid' => $uid,
		)
	);
	$member_found = $db->fetch_assoc($result);
	$db->free_result($result);

	return $member_found;
}

/**
 * Retrieve a member's connected providers
 *
 * @param int $id_member the member's id
 *
 * @return array list of stored providers
 */
function connectedProviders($id_member)
{
	$db = database();

	$result = $db->query('', '
		SELECT 
			provider
		FROM {db_prefix}oauth2_authentications
		WHERE id_member = {int:id}',
		array(
			'id' => $id_member,
		)
	);
	$providers = array();
	while ($row = $db->fetch_assoc($result))
	{
		$providers[] = $row['provider'];
	}
	$db->free_result($result);

	return $providers;
}

/**
 * Add an OAuth connection for a user to the database
 *
 * @param int $id_member the member's id
 * @param string $provider the provider they're using
 * @param string $uid the provider's unique identifier
 * @param string $username the username from the provider
 * @return bool
 */
function addAuth($id_member, $provider, $uid, $username)
{
	$db = database();

	$db->insert('insert', '
		{db_prefix}oauth2_authentications',
		array(
			'id_member' => 'int',
			'provider' => 'string',
			'provider_uid' => 'string',
			'username' => 'string'
		),
		array(
			'id_member' => $id_member,
			'provider' => $provider,
			'provider_uid' => $uid,
			'username' => $username
		),
		array()
	);

	return $db->affected_rows() != 0;
}

/**
 * Remove an auth from the database
 *
 * @param int $id_member the member's id
 * @param string $provider the provider they're using
 * @return bool
 */
function deleteAuth($id_member, $provider)
{
	$db = database();

	$db->query('', '
		DELETE FROM {db_prefix}oauth2_authentications
		WHERE provider = {string:provider}
			AND id_member = {int:id_member}',
		array(
			'id_member' => $id_member,
			'provider' => $provider,
		)
	);

	// Return the success unless an error occurred.
	return $db->affected_rows() != 0;
}

/**
 * Returns the config array of enabled providers for use in hybridauth
 */
function extauth_config()
{
	global $boardurl, $modSettings;

	$providers = extauth_discover_providers();
	$enabled = array();

	// Prepare the HybridAuth config array
	foreach ($providers as $service)
	{
		if (!empty($modSettings['ext_enable_' . $service])
			&& !empty($modSettings['ext_key_' . $service])
			&& !empty($modSettings['ext_secret_' . $service])
		)
		{
			// Standard bits
			$enabled[ucfirst($service)] = array(
				'enabled' => true,
				'keys' => array(
					'key' => $modSettings['ext_key_' . $service],
					'id' => $modSettings['ext_key_' . $service],
					'secret' => $modSettings['ext_secret_' . $service]
				)
			);

			// Special bits
			if ($service === 'facebook')
			{
				$enabled[ucfirst($service)]['trustForwarded'] = false;
				$enabled[ucfirst($service)]['scope'] = array('email', 'user_about_me');
			}
			elseif ($service === 'google')
			{
				$enabled[ucfirst($service)]['scope'] = 'https://www.googleapis.com/auth/userinfo.profile https://www.googleapis.com/auth/userinfo.email';
				$enabled[ucfirst($service)]['access_type'] = 'online';
			}
		}
	}

	return
		array(
			'base_url' => $boardurl . '/sources/ext/hybridauth',
			'providers' => $enabled,
			'debug_mode' => false,
			'debug_file' => CACHEDIR . '/Ext_debug_log.txt'
		);
}

/**
 * Return all enable providers in the system
 *
 * @return array
 */
function extauth_enabled_providers()
{
	// Load the available providers
	$enabled_providers = array();
	$config = extauth_config();

	foreach ($config['providers'] as $name => $provider)
	{
		if ($provider['enabled'])
		{
			$enabled_providers[] = $name;
		}
	}

	return $enabled_providers;
}

/**
 * Reads the Providers directory to find all interface files we can use
 *
 * @return array
 */
function extauth_discover_providers()
{
	global $txt;

	$fileSystemIterator = new FilesystemIterator(EXTDIR . '/hybridauth/Hybrid/Providers');

	$entries = array();
	foreach ($fileSystemIterator as $fileInfo)
	{
		$provider = strtolower($fileInfo->getBasename('.php'));

		// File but no txt details, skip it
		if (!isset($txt['ext_enable_' . $provider]))
		{
			continue;
		}

		$entries[] = $provider;
	}

	sort($entries);

	return $entries;
}

/**
 * Ensure a website URL from a provider is compatible with our profile fields
 *
 * @param string $value
 * @return string
 */
function validate_provider_url($value)
{
	if (strlen(trim($value)) > 0 && strpos($value, '://') === false)
	{
		$value = 'http://' . $value;
	}

	if (strlen($value) < 8 || (substr($value, 0, 7) !== 'http://' && substr($value, 0, 8) !== 'https://'))
	{
		$value = '';
	}

	return $value;
}

/**
 * Validate the display name is suitable
 *
 * @param $value
 * @return string
 */
function validate_provider_display_name($value)
{
	$value = trim(preg_replace('~[\s]~u', ' ', $value));

	if (trim($value) == '')
	{
		return '';
	}
	elseif (Util::strlen($value) > 60)
	{
		return '';
	}
	else
	{
		require_once(SUBSDIR . '/Members.subs.php');
		if (isReservedName($value))
		{
			return '';
		}
	}

	return $value;
}
