<?php

/**
 * @package "ExternalAuth" External Authentication Addon for Elkarte
 * @author Spuds
 * @copyright (c) 2021 Spuds
 * @license No derivative works. No warranty, explicit or implicit, provided.
 * The Software is provided under an AS-IS basis, Licensor shall never, and without any limit,
 * be liable for any damage, cost, expense or any other payment incurred by Licensee as a result
 * of Software’s actions, failure, bugs and/or any other interaction.
 *
 * @version 1.0.6
 *
 * This addon is based on code from:
 * @author Antony Derham
 * @copyright 2014 Antony Derham
 *
 */

/**
 * Profile Menu Hook, integrate_profile_areas, called from Profile.controller.php
 *
 * Used to add menu items to the profile area
 * Adds Connected Accounts to profile menu
 *
 * @param mixed[] $profile_areas
 */
function ipa_extauth(&$profile_areas)
{
	global $user_info, $txt, $modSettings;

	// No need to show these profile option to guests or if the addon is off
	if ($user_info['is_guest'] || empty($modSettings['extauth_master']))
	{
		return;
	}

	$profile_areas['edit_profile']['areas'] =
		elk_array_insert($profile_areas['edit_profile']['areas'], 'account',
		array(
			'extauth' => array(
				'label' => $txt['connect_accounts'],
				'file' => 'Extauth.controller.php',
				'controller' => 'Extauth_Controller',
				'function' => 'action_profile',
				'sc' => 'post',
				'token' => 'profile-ea%u',
				'permission' => array(
					'own' => array('profile_identity_any', 'profile_identity_own'),
					'any' => array('profile_identity_any'),
				),
			),
		), 'after');
}

/**
 * Admin hook, integrate_admin_areas, called from Admin.php
 *
 * - Adds the external authentication button under admin menu members -> registration
 *
 * @param mixed[] $admin_areas
 */
function iaa_extauth(&$admin_areas)
{
	global $txt;

	loadLanguage('Extauth');

	$admin_areas['members']['areas']['regcenter']['subsections']['extauth'] = array($txt['provider_services']);
}

/**
 * integrate_sa_manage_registrations, called from Action controller
 *
 * @param mixed[] $subactions
 */
function ismr_extauth(&$subactions)
{
	$subactions['extauth'] = array(
		'controller' => 'ExtauthAdmin_Controller',
		'dir' => ADMINDIR,
		'file' => 'ExtauthAdmin.controller.php',
		'function' => 'action_providers',
		'permission' => 'admin_forum'
	);
}

/**
 * Integration hook, integrate_action_auth_before, called from dispatcher
 *
 * - Here we use it to add in a template layer for display
 */
function iaab_extauth()
{
	global $context, $modSettings;

	if (empty($modSettings['extauth_master']))
	{
		return;
	}

	// Login Screen ?
	if ((!empty($context['site_action']) && $context['site_action'] === 'auth') && (isset($_GET['action']) && $_GET['action'] === 'login'))
	{
		// Load the enabled providers
		require_once(SUBSDIR . '/Extauth.subs.php');
		$context['enabled_providers'] = extauth_enabled_providers();

		Template_Layers::getInstance()->addBegin('extauth_login');
	}
}

/**
 * Integration hook, integrate_action_register_before, called from dispatcher
 *
 * - Here we use it to add in a template layer for display on the registration form
 */
function iarb_extauth()
{
	global $context, $modSettings;

	if (empty($modSettings['extauth_master']))
	{
		return;
	}

	// Registration Screen ?
	if ((!empty($context['site_action']) && $context['site_action'] === 'register')
		&& (isset($_GET['action']) && $_GET['action'] === 'register'))
	{
		// Load the enabled providers
		require_once(SUBSDIR . '/Extauth.subs.php');
		$context['enabled_providers'] = extauth_enabled_providers();

		if ($modSettings['requireAgreement'] && empty($_POST['accept_agreement']))
		{
			Template_Layers::getInstance()->addBegin('extauth_register');
		}
	}
}

/**
 * Integration hook, integrate_load_theme, called from load.php,
 *
 * Used to add the template to the header where we add the icons next to the login bar
 */
function ilt_extauth()
{
	global $context, $modSettings;

	if (empty($modSettings['extauth_master']))
	{
		return;
	}

	loadCSSFile('Extauth.css');
	loadTemplate('Extauth');
	loadLanguage('Extauth');

	if (!empty($context['show_login_bar']) && !empty($modSettings['extauth_loginbar']))
	{
		require_once(SUBSDIR . '/Extauth.subs.php');
		$context['enabled_providers'] = extauth_enabled_providers();
		$context['theme_header_callbacks'][] = 'extauth_icons';
	}
}

/**
 * Integration hook, integrate_init_theme, Called from Load.php,
 *
 * Used here to turn on FA support in ElkArte 1.1
 */
function iit_extauth()
{
	global $modSettings;

	if (empty($modSettings['require_font-awesome']))
	{
		$modSettings['require_font-awesome'] = true;
	}
}

/**
 * Integration hook, integrate_delete_members, called from Members.subs
 *
 * Used to remove social logins on account deletion
 *
 * @param int[] $users
 */
function idm_extauth($users)
{
	$db = database();

	// Remove individual OAuth settings.
	$db->query('', '
		DELETE FROM {db_prefix}oauth2_authentications
		WHERE id_member IN ({array_int:users})',
		array(
			'users' => $users,
		)
	);
}
