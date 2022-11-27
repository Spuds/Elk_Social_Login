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
 *
 */

/**
 * ExtauthAdmin_Controller class, deals with site settings for external authentication
 */
class ExtauthAdmin_Controller extends Action_Controller
{
	/** @var \Settings_Form */
	protected $extauthProvider;

	public function pre_dispatch()
	{
		require_once(SUBSDIR . '/Action.class.php');
		require_once(SUBSDIR . '/Extauth.subs.php');

		loadCSSFile('Extauth.css');
		loadTemplate('Extauth');
		loadLanguage('Extauth');

		parent::pre_dispatch();
	}

	/**
	 * Entry point in ExtAuth controller
	 */
	public function action_index()
	{
		// Where to go
		$subActions = array(
			'providers' => array($this, 'action_providers')
		);

		$action = new Action();

		// Default action is login
		$subAction = $action->initialize($subActions, 'login');

		// Go!
		$action->dispatch($subAction);
	}

	/**
	 * Shows all available OAuth providers so id/key and secret codes can be
	 * entered.
	 */
	public function action_providers()
	{
		global $txt, $context, $scripturl;

		// Initialize the form
		$this->_init_extauthProvidersForm();

		// Setup the template
		$context['sub_template'] = 'show_settings';
		$context['page_title'] = $txt['provider_services'];

		if (isset($this->_req->query->save))
		{
			checkSession();

			$this->extauthProvider->setConfigValues((array) $this->_req->post);
			$this->extauthProvider->save();

			redirectexit('action=admin;area=regcenter;sa=extauth');
		}

		$context['post_url'] = $scripturl . '?action=admin;area=regcenter;save;sa=extauth';
		$context['settings_title'] = $txt['provider_services_settings'];

		$this->extauthProvider->prepare();
	}

	/**
	 * Initialize settings form with the configuration settings for external authentication
	 */
	private function _init_extauthProvidersForm()
	{
		// Instantiate the form
		$this->extauthProvider = new Settings_Form(Settings_Form::DB_ADAPTER);

		// Initialize it with our settings
		$config_vars = $this->extauthSettings();

		$this->extauthProvider->setConfigVars($config_vars);
	}

	/**
	 * Return configuration settings for external login providers
	 *
	 * @return array
	 */
	protected function extauthSettings()
	{
		global $txt;

		// Are the dependency's here
		$can_enable = function_exists('curl_init') && function_exists('json_decode');
		$subtext = $can_enable ? '' : $txt['extauth_missing_requirements'];

		// These are all we have enabled
		$providers = extauth_discover_providers();

		$config_vars[] = array('check', 'extauth_master', 'disabled' => !$can_enable, 'subtext' => $subtext);
		$config_vars[] = array('check', 'extauth_loginbar');
		$config_vars[] = array('check', 'extauth_noemail');

		$config_vars[] = array('desc', 'provider_services_settings_desc');
		foreach ($providers as $id)
		{
			$config_vars[] = array(
				'check', 'ext_enable_' . $id,
				'postinput' => $txt['ext_api_url_' . $id],
				'onchange' => 'showhideOptions(\'' . $id . '\');',
				'helptext' => $txt['ext_api_url_' . $id . '_help'],
				'help' => $txt['ext_api_url_' . $id . '_help']
			);
			$config_vars[] = array('text', 'ext_key_' . $id, 60);
			$config_vars[] = array('text', 'ext_secret_' . $id, 60);
			$config_vars[] = '';
		}

		// Show hide code tied to checkbox
		addInlineJavascript('
		function showhideOptions(id) {
			var enabled = document.getElementById("ext_enable_" + id).checked;

			if (enabled) {
				$("#ext_key_" + id).parent().slideDown();
				$("#ext_secret_" + id).parent().slideDown();
				$("#setting_ext_key_" + id).parent().slideDown();
				$("#setting_ext_secret_" + id).parent().slideDown();
			}	
			else {
				$("#ext_key_" + id).parent().slideUp();
				$("#ext_secret_" + id).parent().slideUp();
				$("#setting_ext_key_" + id).parent().slideUp();
				$("#setting_ext_secret_" + id).parent().slideUp();
			}
		}
		
		function showhideInit() {
			var providers = ' . json_encode($providers) . ';
			
			providers.forEach(function(name) {
			    showhideOptions(name);
			});
		}
		
		showhideInit();', true);

		return $config_vars;
	}
}
