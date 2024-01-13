<?php

/**
 * @package "ExternalAuth" External Authentication Addon for Elkarte
 * @author Spuds
 * @copyright (c) 2022 Spuds
 * @license No derivative works. No warranty, explicit or implicit, provided.
 * The Software is provided under an AS-IS basis, Licensor shall never, and without any limit,
 * be liable for any damage, cost, expense or any other payment incurred by Licensee as a result
 * of Software’s actions, failure, bugs and/or any other interaction.
 *
 * @version 1.1.1
 *
 * This addon is based on code from:
 * @author Antony Derham
 * @copyright 2014 Antony Derham
 */

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

	return !empty($db->affected_rows());
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
	return !empty($db->affected_rows());
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
 * Reads the Provider directory to find all interface files we can use
 *
 * @return array
 */
function extauth_discover_providers()
{
	global $txt;

	$entries = array();

	if (!is_dir(EXTDIR . '/hybridauth/Hybrid/Providers'))
	{
		return $entries;
	}

	$fileSystemIterator = new FilesystemIterator(EXTDIR . '/hybridauth/Hybrid/Providers');
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
	if (empty($value))
	{
		return '';
	}

	if (strpos($value, '://') === false && strlen(trim($value)) > 0)
	{
		$value = 'https://' . $value;
	}

	if (strlen($value) < 8 || (strpos($value, 'http://') !== 0 && strpos($value, 'https://') !== 0))
	{
		$value = '';
	}

	return $value;
}

/**
 * Validate the display name is suitable
 *
 * - Will create a fake one if what the provider returns is taken
 * - If generated (or not) if this is part of registration, and not a connected account, then the user
 * will NOT be able to change this crazy name (since they don't have a password) ... they would need to
 * reset their password for the account and then change the name, or beg the admin/mod
 *
 * @param $value
 * @return string
 */
function validate_provider_display_name($value)
{
	$value = trim(preg_replace('~[\s]~u', ' ', $value));
	$tokenizer = new Token_Hash();
	$fail_over = $tokenizer->generate_hash(rand(6, 10)) . '_' . $tokenizer->generate_hash(rand(4, 8));


	if (trim($value) === '')
	{
		return $fail_over;
	}

	if (Util::strlen($value) > 60)
	{
		return $fail_over;
	}

	require_once(SUBSDIR . '/Members.subs.php');
	if (isReservedName($value))
	{
		// Perhaps we can save this if another member simply has taken the same "real name"
		if (getMemberByName($value) !== false)
		{
			$value = str_replace(' ', '_', $value);
			for ($i = 0; $i < 100; $i++)
			{
				if (getMemberByName($value) === false)
				{
					return $value;
				}

				$value .= '_' . $i;
			}
		}

		return $fail_over;
	}

	return $value;
}
