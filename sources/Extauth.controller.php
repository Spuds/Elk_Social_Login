<?php

/**
 * @package "ExternalAuth" External Authentication Addon for Elkarte
 * @author Spuds
 * @copyright (c) 2019 Spuds
 * @license No derivative works. No warranty, explicit or implicit, provided.
 * The Software is provided under an AS-IS basis, Licensor shall never, and without any limit,
 * be liable for any damage, cost, expense or any other payment incurred by Licensee as a result
 * of Software’s actions, failure, bugs and/or any other interaction.
 *
 * @version 1.0.5
 *
 * This addon is based on code from:
 * @author Antony Derham
 * @copyright 2014 Antony Derham
 *
 */

/**
 * ExtAuth_Controller class, deals with authenticating external accounts
 */
class Extauth_Controller extends Action_Controller
{
	/**
	 * @var string name of the provider, like Facebook
	 */
	var $provider;
	/**
	 * @var int member id
	 */
	var $member;
	/**
	 * @var Hybrid_Auth
	 */
	var $hybridauth;
	/**
	 * @var Hybrid_Provider_Adapter
	 */
	var $profile;

	/**
	 * Called on entry, used to add any dependency's
	 */
	public function pre_dispatch()
	{
		global $modSettings;

		// Off means off
		if (empty($modSettings['extauth_master']))
		{
			redirectexit();
		}

		require_once(SUBSDIR . '/Extauth.subs.php');
		require_once(EXTDIR . '/hybridauth/Hybrid/Auth.php');

		// Load in some 1.0 compatibility functions so this controller works with 1.0 and 1.1
		if (defined('FORUM_VERSION') && substr(FORUM_VERSION, 8, 3) === '1.1')
		{
			require_once(SOURCEDIR . '/Errors.php');
		}

		$this->provider = isset($_GET['provider']) ? ucfirst(trim($_GET['provider'])) : '';
		$this->member = isset($_GET['member']) ? (int) $_GET['member'] : 0;

		parent::pre_dispatch();
	}

	/**
	 * Entry point in ExtAuth controller, dispatches to the right functions
	 */
	public function action_index()
	{
		require_once(SUBSDIR . '/Action.class.php');

		// Where to go
		$subActions = array(
			'login' => array($this, 'action_extlogin'),
			'auth' => array($this, 'action_auth'),
			'deauth' => array($this, 'action_deauth'),
			'register' => array($this, 'action_register'),
			'register2' => array($this, 'action_register2'),
			'profile' => array($this, 'action_profile'),
			'providers' => array($this, 'action_providers')
		);

		$action = new Action();

		// Default action is login
		$subAction = $action->initialize($subActions, 'login');

		// Go!
		$action->dispatch($subAction);
	}

	/**
	 * Attempt to authenticate a user to a selected provider
	 *
	 * What it does:
	 *  - Takes the provider name from GET and attempts authentication.
	 *  - Approved connections are saved to the DB
	 */
	public function action_extlogin()
	{
		global $user_settings;

		// No provider the we go back to login.
		if (empty($this->provider))
		{
			redirectexit('action=login');
		}

		// Lets use the HybridAuth library then
		try
		{
			// Fetch the configuration and start a new HybridAuth instance
			$this->initHybridAuth();

			// Authenticate the user with the provider
			$this->getAdapterProfile();

			// Find them in the database
			$member_found = memberByExtUID($this->provider, $this->profile->identifier);

			// If the member was already linked, its a login!
			if ($member_found)
			{
				$user_settings = $member_found;

				require_once(CONTROLLERDIR . '/Auth.controller.php');
				loadLanguage('Login');

				// Make sure they are activated
				if (!checkActivation())
				{
					global $context;

					$message = isset($context['login_errors'][0]) ? $context['login_errors'][0] : 'unkonwn';
					fatal_error($message, false);
				}

				// Return to our standard Login flow
				doLogin();
			}
			// Not a member yet, or not connected, so they need to register first.
			else
			{
				// Save data that the provider *may* have returned
				$this->setProviderSessionData();

				// Send them to our external authorization register page
				redirectexit('action=extauth;sa=register;provider=' . $this->provider);
			}
		}
		catch (Exception $e)
		{
			global $txt;

			unset($_SESSION['extauth_info']);
			$message = $txt['extauth_error_' . $e->getCode()] . ' :: ' . substr($e->getMessage(), 0, 128);
			fatal_error($message, false);
		}
	}

	/**
	 * Cleans and sets any provider data that we want to save
	 */
	private function setProviderSessionData()
	{
		// Not all providers provide all the data, and not all init vars.
		$this->profile->gender = !empty($this->profile->gender) ? $this->profile->gender : '';
		$this->profile->photoURL = !empty($this->profile->photoURL) ? $this->profile->photoURL : '';
		$this->profile->description = !empty($this->profile->description) ? $this->profile->description : '';
		$this->profile->webSiteURL = !empty($this->profile->webSiteURL) ? $this->profile->webSiteURL : '';

		// Save data that the provider *may* have returned
		$_SESSION['extauth_info'] = array(
			'provider' => $this->provider,
			'uid' => $this->profile->identifier,
			'name' => validate_provider_display_name(Util::htmlspecialchars($this->profile->displayName, ENT_QUOTES)),
			'email' => $this->profile->email,
			'avatar' => Util::htmlspecialchars($this->profile->photoURL, ENT_QUOTES),
			'blurb' => Util::shorten_text(Util::htmlspecialchars($this->profile->description, ENT_QUOTES), 50, true),
			'website' => validate_provider_url(Util::htmlspecialchars($this->profile->webSiteURL, ENT_QUOTES)),
			'gender' => $this->profile->gender === 'male' ? 1 : ($this->profile->gender === 'female' ? 2 : 0),
		);
	}

	/**
	 * Get an instance of Hybrid_Auth with the sites config values
	 */
	private function initHybridAuth()
	{
		// Fetch the configuration and start a new HybridAuth instance
		$config = extauth_config();
		$this->hybridauth = new Hybrid_Auth($config);
	}

	/**
	 * Authenticate the user with the provider, ie Google
	 *
	 * - Try to authenticate the user with the provider
	 * - User will be redirected to the provider for authentication
	 * - If he already connected/authorized, then it will
	 * return an instance of the adapter
	 * - Profile information that is available from the provider will be loaded
	 */
	private function getAdapterProfile()
	{
		try
		{
			// Try to authenticate the user with a given provider.
			$adapter = $this->hybridauth->authenticate($this->provider);

			// Get what we can about this user from the provider
			$this->profile = $adapter->getUserProfile();
		}
		catch (Exception $e)
		{
			// If we fail, log out all providers and try again
			$this->hybridauth->logoutAllProviders();
			$this->initHybridAuth();
			$adapter = $this->hybridauth->authenticate($this->provider);
			$this->profile = $adapter->getUserProfile();
		}

		unset($_SESSION['request_referer']);
	}

	/**
	 * Connect a user account with an enabled provider
	 *
	 * - Called from profile screen when a user chooses to connect
	 */
	public function action_auth()
	{
		// No provider or user the we go back.
		if (!empty($this->provider) && !empty($this->member))
		{
			// Make an OAuth connection to the provider
			try
			{
				$this->initHybridAuth();
				$this->getAdapterProfile();
				$member_found = memberByExtUID($this->provider, $this->profile->identifier);

				// If the member is not already linked then save this valid authorization
				if (!$member_found)
				{
					// Create an authentication entry in the db
					addAuth($this->member, $this->provider, $this->profile->identifier, $this->profile->displayName);
				}
			}
			catch (Exception $e)
			{
				global $txt;

				unset($_SESSION['extauth_info']);
				$message = $txt['extauth_error_' . $e->getCode()] . ' :: ' . substr($e->getMessage(), 0, 128);
				fatal_error($message, false);
			}

			// Back to the profile page
			redirectexit('action=profile;area=extauth');
		}
	}

	/**
	 * Remove a previous authorized connection for a provider
	 *
	 * - Called from user profile when they want to remove a connection
	 */
	public function action_deauth()
	{
		// If they are using this, log them out
		try
		{
			$this->initHybridAuth();
			if (Hybrid_Auth::isConnectedWith($this->provider))
			{
				$adapter = Hybrid_Auth::getAdapter($this->provider);
				$adapter->logout();
			}
		}
		catch (Exception $e)
		{
			global $txt;

			unset($_SESSION['extauth_info']);
			$message = $txt['extauth_error_' . $e->getCode()] . ' :: ' . substr($e->getMessage(), 0, 128);
			fatal_error($message, false);
		}

		// Remove entry from db
		deleteAuth($this->member, $this->provider);

		// Back to the profile we go
		redirectexit('action=profile;area=extauth');
	}

	/**
	 * Called from user profile to show connected *and* available providers
	 *
	 * - Used to show a list of available providers
	 * - User can enable / remove connections (auth/deuath) for each
	 */
	public function action_profile()
	{
		global $context;

		$memID = currentMemberID();

		// Fetch any providers this user has activated
		$context['connected_providers'] = connectedProviders($memID);

		// Providers available
		$context['enabled_providers'] = extauth_enabled_providers();
	}

	/**
	 * Begin the registration process.
	 *
	 * - Cut down version of action_register from Register.controller.php
	 *
	 * @uses Extauth template, registration sub template
	 * @uses Login, Extauth, Errors, Profile language files
	 */
	public function action_register()
	{
		global $context, $modSettings, $txt, $user_info;

		// First which provider is this?
		$context['provider'] = isset($_GET['provider']) ? ucfirst(strtolower($_GET['provider'])) : '';

		// Check if the administrator has it disabled.
		if (!empty($modSettings['registration_method']) && $modSettings['registration_method'] == '3')
		{
			fatal_lang_error('registration_disabled', false);
		}
		// You are not a guest, so you are a member - and members don't get to register twice!
		elseif (empty($user_info['is_guest']))
		{
			redirectexit();
		}
		// Need a valid provider to authenticate with
		elseif (empty($context['provider']) || !in_array($context['provider'], extauth_enabled_providers()))
		{
			redirectexit();
		}

		// ExtAuth registration template
		$context['sub_template'] = 'registration';
		loadLanguage('Login');

		// If you have to agree to the agreement, it needs to be fetched from the file.
		$context['require_agreement'] = !empty($modSettings['requireAgreement']);
		$context['registration_passed_agreement'] = !empty($_SESSION['registration_agreed']);
		if ($context['require_agreement'])
		{
			// Have we got a localized one?
			if (file_exists(BOARDDIR . '/agreement.' . $user_info['language'] . '.txt'))
			{
				$context['agreement'] = parse_bbc(file_get_contents(BOARDDIR . '/agreement.' . $user_info['language'] . '.txt'), true, 'agreement_' . $user_info['language']);
			}
			elseif (file_exists(BOARDDIR . '/agreement.txt'))
			{
				$context['agreement'] = parse_bbc(file_get_contents(BOARDDIR . '/agreement.txt'), true, 'agreement');
			}
			else
			{
				$context['agreement'] = '';
			}

			// Nothing to show, lets disable registration and inform the admin of this error
			if (empty($context['agreement']))
			{
				// No file found or a blank file, log the error so the admin knows there is a problem!
				loadLanguage('Errors');
				log_error($txt['registration_agreement_missing'], 'critical');
				fatal_lang_error('registration_disabled', false);
			}
		}

		// Any custom fields we want filled in?
		require_once(SUBSDIR . '/Profile.subs.php');
		loadCustomFields(0, 'register');

		// Or any standard ones?
		if (!empty($modSettings['registration_fields']))
		{
			// Setup some important context.
			loadLanguage('Profile');
			loadTemplate('Profile');

			$context['user']['is_owner'] = true;

			// Here, and here only, emulate the permissions the user would have to do this.
			$user_info['permissions'] = array_merge($user_info['permissions'], array('profile_account_own', 'profile_extra_own'));
			$reg_fields = explode(',', $modSettings['registration_fields']);

			// We might have had some submissions on this front - go check.
			foreach ($reg_fields as $field)
			{
				if (isset($_POST[$field]))
				{
					$cur_profile[$field] = Util::htmlspecialchars($_POST[$field]);
				}
			}

			// Load all the fields in question.
			setupProfileContext($reg_fields, 'registration');
		}

		// No need for this control when registering with a provider
		$context['visual_verification'] = false;

		// Were there any errors?
		$context['registration_errors'] = array();

		$reg_errors = $this->initError();
		if ($reg_errors->hasErrors())
		{
			$context['registration_errors'] = $reg_errors->prepareErrors();
		}

		// Any information that we received from the provider that may be useful for the template
		$context['email'] = isset($_SESSION['extauth_info']['email']) ? $_SESSION['extauth_info']['email'] : '';

		createToken('register');
	}

	/**
	 * Initialize the proper error context handler for the forum
	 */
	private function initError()
	{
		if (defined('FORUM_VERSION') && substr(FORUM_VERSION, 8, 3) === '1.1')
		{
			$reg_errors = ElkArte\Errors\ErrorContext::context('register', 0);
		}
		else
		{
			$reg_errors = Error_Context::context('register', 0);
		}

		return $reg_errors;
	}

	/**
	 * Actually register the member.
	 *
	 * - Modified version of standard action_register2 to work with external authentications
	 */
	public function action_register2()
	{
		global $txt, $modSettings, $context, $user_info;

		// Start collecting together any errors.
		$reg_errors = $this->initError();

		// Check they are who they should be
		checkSession();

		if (!validateToken('register', 'post', true, false))
		{
			$reg_errors->addError('token_verification');
		}

		// You can't register if it's disabled.
		if (!empty($modSettings['registration_method']) && $modSettings['registration_method'] == 3)
		{
			fatal_lang_error('registration_disabled', false);
		}

		// Well, if you don't agree, you can't register.
		if (!empty($modSettings['requireAgreement']) && !isset($_POST['checkbox_agreement']))
		{
			$reg_errors->addError('agreement_unchecked');
		}

		// Make sure they came from *somewhere*, have a session.
		if (!isset($_SESSION['old_url']))
		{
			redirectexit('action=extauth;sa=register;provider=' . $_SESSION['extauth_info']['provider']);
		}

		// You are doing something wrong, register flows through login ;)
		if (empty($_SESSION['extauth_info']['provider']))
		{
			redirectexit();
		}

		// Check their provider details match up correctly in case they're pulling something funny
		if ($_POST['provider'] !== $_SESSION['extauth_info']['provider'])
		{
			redirectexit('action=extauth;sa=register;provider=' . $_SESSION['extauth_info']['provider']);
		}

		// Clean up
		foreach ($_POST as $key => $value)
		{
			if (!is_array($_POST[$key]))
			{
				$_POST[$key] = htmltrim__recursive(str_replace(array("\n", "\r"), '', $_POST[$key]));
			}
		}

		// Need some support
		require_once(SUBSDIR . '/Members.subs.php');
		require_once(SUBSDIR . '/Auth.subs.php');

		// Activation required?
		$require = empty($modSettings['registration_method']) || ($modSettings['registration_method'] == '1' && !empty($modSettings['extauth_noemail']))
			? 'nothing'
			: ($modSettings['registration_method'] == 1
				? 'activation'
				: 'approval');

		// Set the options needed for registration.
		$regOptions = array(
			'interface' => 'guest',
			'username' => !empty($_POST['user']) ? $_POST['user'] : '',
			'email' => !empty($_POST['email']) ? $_POST['email'] : '',
			'check_reserved_name' => true,
			'check_password_strength' => false,
			'check_email_ban' => true,
			'send_welcome_email' => !empty($modSettings['send_welcomeEmail']),
			'require' => $require,
			'gender' => !empty($_SESSION['extauth_info']['gender']) ? $_SESSION['extauth_info']['gender'] : '',
			'hide_email' => !empty($_POST['allow_email']) ? 0 : 1,
			'real_name' => !empty($_SESSION['extauth_info']['name']) ? $_SESSION['extauth_info']['name'] : '',
			'theme_vars' => array(),
		);

		// Extras that we may have received from the social network
		$regOptions['extra_register_vars']['signature'] = !empty($_SESSION['extauth_info']['blurb']) ? $_SESSION['extauth_info']['blurb'] : '';
		$regOptions['extra_register_vars']['avatar'] = !empty($_SESSION['extauth_info']['avatar']) ? $_SESSION['extauth_info']['avatar'] : '';
		$regOptions['extra_register_vars']['website_url'] = !empty($_SESSION['extauth_info']['website']) ? $_SESSION['extauth_info']['website'] : '';

		// Check whether we have fields that simply MUST be displayed?
		require_once(SUBSDIR . '/Profile.subs.php');
		loadCustomFields(0, 'register');

		foreach ($context['custom_fields'] as $row)
		{
			// Don't allow overriding of the theme variables.
			if (isset($regOptions['theme_vars'][$row['colname']]))
			{
				unset($regOptions['theme_vars'][$row['colname']]);
			}

			// Prepare the value!
			$value = isset($_POST['customfield'][$row['colname']]) ? trim($_POST['customfield'][$row['colname']]) : '';

			// We only care for text fields as the others are valid to be empty.
			if (!in_array($row['type'], array('check', 'select', 'radio')))
			{
				// Is it too long?
				if ($row['field_length'] && $row['field_length'] < Util::strlen($value))
				{
					$reg_errors->addError(array('custom_field_too_long', array($row['name'], $row['field_length'])));
				}

				// Any masks to apply?
				if ($row['type'] === 'text' && !empty($row['mask']) && $row['mask'] !== 'none')
				{
					// @todo We never error on this - just ignore it at the moment...
					if ($row['mask'] === 'email' && !isValidEmail($value))
					{
						$reg_errors->addError(array('custom_field_invalid_email', array($row['name'])));
					}
					elseif ($row['mask'] === 'number' && preg_match('~[^\d]~', $value))
					{
						$reg_errors->addError(array('custom_field_not_number', array($row['name'])));
					}
					elseif (substr($row['mask'], 0, 5) === 'regex' && trim($value) !== '' && preg_match(substr($row['mask'], 5), $value) === 0)
					{
						$reg_errors->addError(array('custom_field_inproper_format', array($row['name'])));
					}
				}
			}

			// Is this required but not there?
			if (trim($value) == '' && $row['show_reg'] > 1)
			{
				$reg_errors->addError(array('custom_field_empty', array($row['name'])));
			}
		}

		// Lets check for other errors before trying to register the member.
		if ($reg_errors->hasErrors())
		{
			return $this->action_register();
		}

		// Need to give them a password to protect the account
		mt_srand(time() + 1277);
		$regOptions['password'] = generateValidationCode();
		$regOptions['password_check'] = $regOptions['password'];

		// Registration needs to know your IP
		$req = request();
		$regOptions['ip'] = $user_info['ip'];
		$regOptions['ip2'] = $req->ban_ip();

		$memberID = registerMember($regOptions, 'register');

		// If there are "important" errors and you are not an admin: log the first error
		// Otherwise grab all of them and don't log anything
		if ($reg_errors->hasErrors(1) && !$user_info['is_admin'])
		{
			foreach ($reg_errors->prepareErrors(1) as $error)
			{
				fatal_error($error, 'general');
			}
		}

		// One last error check
		if ($reg_errors->hasErrors())
		{
			// Going back to the register form then
			$_GET['provider'] = $_SESSION['extauth_info']['provider'];
			$context['username'] = $regOptions['username'];
			$context['email'] = $regOptions['email'];

			return $this->action_register();
		}

		// Do our spam protection now.
		spamProtection('register');

		// Associate the member's external account to this new ElkArte account
		addAuth($memberID, $_SESSION['extauth_info']['provider'], $_SESSION['extauth_info']['uid'], $_SESSION['extauth_info']['name']);

		// Is a final approval, like admin needed?
		if ($require !== 'nothing')
		{
			loadTemplate('Register');

			$context += array(
				'page_title' => $txt['register'],
				'title' => $txt['registration_successful'],
				'sub_template' => 'after',
				'description' => $modSettings['registration_method'] == 2 ? $txt['approval_after_registration'] : $txt['activate_after_registration']
			);
		}
		else
		{
			call_integration_hook('integrate_activate', array($regOptions['username']));
			setLoginCookie(60 * $modSettings['cookieTime'], $memberID, hash('sha256', $regOptions['password'] . $regOptions['register_vars']['password_salt']));
			redirectexit('action=extauth;provider=' .  $_SESSION['extauth_info']['provider'], $context['server']['needs_login_fix']);
		}

		return true;
	}
}
