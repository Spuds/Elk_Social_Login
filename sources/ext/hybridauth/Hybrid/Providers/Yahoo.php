<?php

/* !
 * HybridAuth
 * http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
 * (c) 2009-2012, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html
 */

/**
 * Yahoo OAuth Class.
 *
 * @package  HybridAuth providers package
 * @author   Lukasz Koprowski <azram19@gmail.com>
 * @author   Oleg Kuzava <olegkuzava@gmail.com>
 * @version  1.0
 * @license  BSD License
 */

/**
 * Hybrid_Providers_Yahoo - Yahoo provider adapter based on OAuth2 protocol.
 */
class Hybrid_Providers_Yahoo extends Hybrid_Provider_Model_OAuth2 {

    /**
     * Define Yahoo scopes.
     *
     * @var array $scope
     *   If empty will be used YDN App scopes.
     * @see https://developer.yahoo.com/oauth2/guide/yahoo_scopes.
     */
    public $scope = array();

    /**
     * {@inheritdoc}
     */
    function initialize() {
        parent::initialize();

        // Provider api end-points.
        $this->api->api_base_url = "https://api.login.yahoo.com/openid/v1/";
        $this->api->authorize_url = "https://api.login.yahoo.com/oauth2/request_auth";
        $this->api->token_url = "https://api.login.yahoo.com/oauth2/get_token";

        // Set token headers.
        $this->setAuthorizationHeaders("basic");
    }

    /**
     * {@inheritdoc}
     */
    function loginBegin() {
        if (is_array($this->scope)) {
            $this->scope = implode(",", $this->scope);
        }
        parent::loginBegin();
    }

    /**
     * {@inheritdoc}
     */
    function getUserProfile() {
        $this->getCurrentUserId();

		$data = $this->api->api('userinfo');

        if (!isset($data->sub)) {
            throw new Exception("User profile request failed! {$this->providerId} returned an invalid response: " . Hybrid_Logger::dumpData($data), 6);
        }

        $this->user->profile->identifier = $data->sub ?? '';
        $this->user->profile->firstName = $data->given_name ?? '';
        $this->user->profile->lastName = $data->family_name ?? '';
        $this->user->profile->displayName = isset($data->name) ? trim($data->name) : '';
        $this->user->profile->gender = $data->gender ?? '';
		$this->user->profile->language = $data->locale ?? '';
		$this->user->profile->email = $data->email ?? '';

		$this->user->profile->emailVerified = !empty($data->email_verified) ? $this->user->profile->email : '';

		$profileImages = $data->profile_images;
		if (isset($this->config['photo_size'])) {
			$prop = 'image' . $this->config['photo_size'];
		} else {
			$prop = 'image192';
		}
		$this->user->profile->photoURL = $profileImages->$prop;

        return $this->user->profile;
    }

    /**
     * {@inheritdoc}
     */
    function getUserContacts() {
        $userId = $this->getCurrentUserId();

        $response = $this->api->get("user/{$userId}/contacts", array(
            "format" => "json",
            "count" => "max",
        ));

        if ($this->api->http_code != 200) {
            throw new Exception("User contacts request failed! {$this->providerId} returned an error: " . $this->errorMessageByStatus());
        }

        if (!isset($response->contacts) || !isset($response->contacts->contact) || (isset($response->errcode) && $response->errcode != 0)) {
            return array();
        }

        $contacts = array();
        foreach ($response->contacts->contact as $item) {
            $uc = new Hybrid_User_Contact();

            $uc->identifier = isset($item->id) ? $item->id : "";
            $uc->email = $this->selectEmail($item->fields);
            $uc->displayName = $this->selectName($item->fields);
            $uc->photoURL = $this->selectPhoto($item->fields);

            $contacts[] = $uc;
        }

        return $contacts;
    }

    /**
     * Returns current user id.
     *
     * @throws Exception
     */
    function getCurrentUserId() {
        // Set headers to get refresh token if needed
        $this->setAuthorizationHeaders("basic");

        // Refresh tokens if needed.
        $this->refreshToken();

        // Set headers to make api call.
        $this->setAuthorizationHeaders("bearer");
    }

    /**
     * Utility function for returning values from XML-like objects.
     *
     * @param stdClass $vs
     *   Object.
     * @param string $t
     *   Property name.
     * @return mixed
     */
    private function select($vs, $t) {
        foreach ($vs as $v) {
            if ($v->type == $t) {
                return $v;
            }
        }

        return null;
    }

    /**
     * Parses user name.
     *
     * @param stdClass $v
     *   Object.
     * @return string
     *   User name.
     */
    private function selectName($v) {
        $s = $this->select($v, "name");
        if (!$s) {
            $s = $this->select($v, "nickname");
            return isset($s->value) ? $s->value : "";
        }
        return isset($s->value) ? "{$s->value->givenName} {$s->value->familyName}" : "";
    }

    /**
     * Parses photo URL.
     *
     * @param stdClass $v
     *   Object.
     * @return string
     *   Photo URL.
     */
    private function selectPhoto($v) {
        $s = $this->select($v, "image");

        return isset($s->value) ? $s->value->imageUrl : "";
    }

    /**
     * Parses email.
     *
     * @param stdClass $v
     *   Object
     * @return string
     *   An email address.
     */
    private function selectEmail($v) {
        $s = $this->select($v, "email");
        if (empty($s)) {
            $s = $this->select($v, "yahooid");
            if (isset($s->value) && strpos($s->value, "@") === FALSE) {
                $s->value .= "@yahoo.com";
            }
        }

        return isset($s->value) ? $s->value : "";
    }

    /**
     * Set correct Authorization headers.
     *
     * @param string $token_type
     *   Specify token type.
     *
     * @return void
     */
    private function setAuthorizationHeaders($token_type) {
        switch ($token_type) {
            case "basic":
                // The /get_token requires authorization header.
                $token = base64_encode("{$this->config["keys"]["id"]}:{$this->config["keys"]["secret"]}");
                $this->api->curl_header = array(
                    "Authorization: Basic {$token}",
                    "Content-Type: application/x-www-form-urlencoded",
                );
                break;

            case "bearer":
                // Yahoo API requires the token to be passed as a Bearer within the authorization header.
                $this->api->curl_header = array(
                    "Authorization: Bearer {$this->api->access_token}",
                );
                break;
        }
    }
}
