<?php

/*!
* HybridAuth
* http://hybridauth.sourceforge.net | https://github.com/hybridauth/hybridauth
*  (c) 2009-2011 HybridAuth authors | hybridauth.sourceforge.net/licenses.html
*/

/**
 * Hybrid_Providers_Paypal
 */
class Hybrid_Providers_Paypal extends Hybrid_Provider_Model_OAuth2
{
	// default permissions
	public $scope = "openid profile email";

	/**
	 * Wrappers initializer
	 */
	function initialize()
	{
		parent::initialize();

		// Provider api end-points
		$this->api->api_base_url = "https://api.paypal.com/";
		$this->api->authorize_url = "https://www.paypal.com/signin/authorize";
		$this->api->token_url = "https://api.paypal.com/v1/oauth2/token";

		// Paypal requires an access_token in the header
		$this->setAuthorizationHeaders();
	}

	/**
	 * load the user profile from the api client
	 */
	function getUserProfile()
	{
		$headers = [
			'Content-Type' => 'application/json',
		];

		$parameters = [
			'schema' => 'paypalv1.1'
		];

		$data = $this->api->get('v1/identity/oauth2/userinfo', $parameters, $headers);

		if (!isset($data->user_id))
		{
			throw new Exception("User profile request failed! {$this->providerId} returned an invalid response.", 6);
		}

		$this->user->profile->identifier = $data->user_id;
		$this->user->profile->firstName = $data->given_name;
		$this->user->profile->lastName = $data->family_name;
		$this->user->profile->displayName = $data->name;

		$this->user->profile->address = $data->address->street_address ?? '';
		$this->user->profile->city = $data->address->locality ?? '';
		$this->user->profile->country = $data->address->country ?? '';
		$this->user->profile->region = $data->address->region ?? '';
		$this->user->profile->zip = $data->address->postal_code ?? '';

		$emails = (array) $data->emails;
		foreach ($emails as $email)
		{
			if ($email->confirmed)
			{
				$this->user->profile->emailVerified = $email->value;
			}

			if ($email->primary)
			{
				$this->user->profile->email = $email->value;
			}
		}
	}
}
