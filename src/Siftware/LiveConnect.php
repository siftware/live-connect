<?php
/*
 * This file is part of the siftware/live-connect package.
 *
 * (c) 2014 Siftware <code@siftware.com>
 *
 * Author - Darren Beale @bealers
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Siftware;

use Siftware\TokenStore\TokenStoreFactory;

/**
* PHP wrapper to Microsoft's Live Connect API
*
* Uses OAuth 2.0 authorization code grant flow, as documented here:
* http://msdn.microsoft.com/en-us/library/live/hh243647.aspx
*
* Most of the MS examples use Javascript, this is the best resource for a general
* explanation of the Live Connect auth process on the server side:
* http://msdn.microsoft.com/en-us/library/live/hh243649.aspx
*
* See README.md
*/
class LiveConnect
{

    protected $liveId;
    protected $liveSecret;
    protected $redirectUrl;

    protected $token;
    protected $scopes;

    public $debug;

    // --

    public function __construct($liveId, $liveSecret, $redirectUrl)
    {
        $this->liveId       = $liveId;
        $this->liveSecret   = $liveSecret;
        $this->redirectUrl  = $redirectUrl;

        // wl.offline also gives us access to the refresh token
        $this->scopes       = "wl.offline_access,wl.signin,wl.basic";

        $this->debug        = false;

        $this->tokenStore   = TokenStoreFactory::build();
    }

    /**
    * Multi-purpose authentication method, either kick off authentication
    * or refresh an expired token and add the new one to the store
    *
    * Use of this method is optional if you *know* that you have a valid refresh token.
    * It may be more useful at bootstrapping stage but on subsequent requests
    * the liveRequest() method will also check the token expiry & refresh if needed
\    */
    public function authenticate($code = "")
    {
        $storedTokens = $this->tokenStore->getTokens();
        if ($storedTokens)
        {
            $expiry = $storedTokens['token_expires'];

            if ($this->debug) $this->logger("Retrieved tokens from store, expiry: " . $expiry);

            // do we need a refresh?
            if (time() > (int) $expiry) {

                $authTokens = $this->getLiveTokens($storedTokens['refresh_token'], true);

            } else {

                return true;
            }

        } else {

            if ($this->debug) $this->logger("No auth tokens stored");

            if ($code === "") {

                // The first step of the OAuth process, grab a code
                $liveAuthUrl = "https://login.live.com/oauth20_authorize.srf" .
                    "?response_type=code" .
                    "&client_id=" .  $this->liveId .
                    "&scope=" . $this->scopes .
                    "&redirect_uri=" . urlencode($this->redirectUrl);

                if ($this->debug) $this->logger("Requesting an auth code to URL: " . $liveAuthUrl);

                header("Location: " . $liveAuthUrl);

            } else {
                // Step 2, grab some auth tokens
                $authTokens = $this->getLiveTokens($code, false);
            }
        }

        // save
        if (isset($authTokens))
        {
            if (is_object($authTokens) && $this->tokenStore->saveTokens($authTokens)) {

                if ($this->debug) $this->logger("Saving tokens");
                return true;

            } else {

                $this->logger("Problem storing tokens");
            }
        }

        // default
        return false;
    }

    // --

    public function getLiveTokens($key, $refresh = false)
    {
        $authUrl = "https://login.live.com";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $authUrl . "/oauth20_token.srf");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/x-www-form-urlencoded',
        ));
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $payload = array(
            "client_id"     => $this->liveId,
            "client_secret" => $this->liveSecret,
            // do not URL encode this, http_build_query does that for you
            "redirect_uri"  => $this->redirectUrl
        );

        if ($refresh) {
            $payload["refresh_token"] = $key;
            $payload["grant_type"] = "refresh_token";
        }
        else
        {
            $payload["code"] = $key;
            $payload["grant_type"] = "authorization_code";
        }

        if ($this->debug) {

            $this->logger("Attempting to retrieve (" . ($refresh ? "Refresh" : "New") .
                ") authorisation_token from Live Connect, payload: " .
                http_build_query($payload));
        }

        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));

        try {

            $response = curl_exec($ch);

        } catch (Exception $e) {

            $this->logger("Error connecting to Live Connect: " .
                $e->message . "(" . $e->code . ")");
            return false;
        }

        $responseJson = json_decode($response);

        if (is_object($responseJson) && property_exists($responseJson, 'access_token'))
        {
            return $responseJson;
        }
        elseif(is_object($responseJson) && property_exists($responseJson, 'error'))
        {
            $this->logger("Error getting access token from Live Connect: " .
                $responseJson->error . ' : ' . $responseJson->error_description);

            return false;

        } else {
            $this->logger("Unknown error getting access token from Live Connect, invalid payload?");
            return false;
        }
    }

    /**
    * @param string $url
    * @param string $method
    * @param array $params
    *
    * @return mixed, object on success false on failure
    *
    * @TODO this needs to cater for POST/PUT/DELETE etc
    */
    public function liveRequest($url, $method = 'GET', $params = array())
    {
        // check that the token hasn't now expired
        $accessToken = $this->getAccessToken();
        if (!$accessToken) return false;

        $requestUrl = $url .'?'. http_build_query($params);

        if ($this->debug) $this->logger("Making Live Connect request: " . $requestUrl);

        $ch = curl_init($requestUrl);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $accessToken));

        try{
            $response = curl_exec($ch);
        }
        catch (Exception $e)
        {
            $this->logger("Error connecting to Live Connect: " . $e->message . "(" . $e->code . ")");
            return false;
        }

        $responseJson = json_decode($response);

        if(is_object($responseJson) && property_exists($responseJson, 'error'))
        {
            $this->logger("Error during Live Connect request: " .
                $responseJson->error . ' : ' . $responseJson->error_description);

            return false;

        } else {
            return $responseJson;
        }
    }

    /**
    * Grab the access token, but authenticate first, useful for requests
    */
    private function getAccessToken()
    {
        if ($this->authenticate()) {
            $authTokens = $this->tokenStore->getTokens();
            return $authTokens["access_token"];
        }
        else
        {
            return false;
        }
    }

    // --

    /**
    * Very basic logging
    *
    * @TODO introduce dependency of Monolog
    */
    private function logger($string)
    {
        // not sure if this is strictly necessary
        try {
            error_log($string);
        }
        catch (Exception $e)
        {
            die("Unable to call PHP's error_log() function");
        }
    }

    // -- basic getters & setters

    /**
    * comma separated string of Live Connect scopes
    *
    * @param string $scopes
    */
    public function setScopes($scopes)
    {
        $this->scopes = $scopes;
    }

    // --

    public function setDebug($debug)
    {
        $this->debug = $debug;
    }

    // -- Basic API interaction, post authentication

    /**
    * @param string $guid
    * @return object
    */
    public function getProfile($guid = 'me')
    {
        return $this->liveRequest('https://apis.live.net/v5.0/' . $guid, 'GET');
    }

    /**
    * @param array $params
    * @return object
    */
    public function getContacts($guid = 'me', $params = array())
    {
        return $this->liveRequest('https://apis.live.net/v5.0/' . $guid . '/contacts', 'GET', $params);
    }

}