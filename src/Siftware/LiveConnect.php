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
use Siftware\Logger;
use Siftware\LiveRequest;

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

            Logger::debug("Retrieved tokens from store, expiry: " . $expiry);

            // do we need a refresh?
            if (time() > (int) $expiry) {

                $authTokens = $this->getLiveTokens($storedTokens['refresh_token'], true);

            } else {

                return true;
            }

        } else {

            Logger::debug("No auth tokens stored");

            if ($code === "") {

                // The first step of the OAuth process, grab a code
                $liveAuthUrl = "https://login.live.com/oauth20_authorize.srf" .
                    "?response_type=code" .
                    "&client_id=" .  $this->liveId .
                    "&scope=" . $this->scopes .
                    "&redirect_uri=" . urlencode($this->redirectUrl);

                Logger::debug("Requesting an auth code to URL: " . $liveAuthUrl);

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

                Logger::debug("Saving tokens");
                return true;

            } else {

                Logger::error("Problem storing tokens");
            }
        }

        // default
        return false;
    }

    // --

    public function getLiveTokens($key, $refresh = false)
    {
        $authUrl = "https://login.live.com/oauth20_token.srf";

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

        $client = new LiveRequest($authUrl);

        Logger::debug("Attempting to retrieve (" .
                        ($refresh ? "Refresh" : "New") .
                        ") authorisation_token from Live Connect, payload: " .
                        http_build_query($payload));

        $response = $client->post($payload);
        $responseJson = json_decode($response);

        if (is_object($responseJson) && property_exists($responseJson, 'access_token'))
        {
            return $responseJson;
        }
        elseif(is_object($responseJson) && property_exists($responseJson, 'error'))
        {
            Logger::error("Error getting access token from Live Connect: " .
                $responseJson->error . ' : ' . $responseJson->error_description);

            return false;

        } else {
            Logger::error("Unknown Error");
            return false;
        }
    }

//    /**
//    * @param string $url
//    * @param string $method
//    * @param array $params
//    *
//    * @return mixed, object on success false on failure
//    *
//    * @TODO this needs to cater for POST/PUT/DELETE etc
//    */
//    public function liveRequest($url, $method = 'GET', $params = array())
//    {
//        // check that the token hasn't now expired
//        $accessToken = $this->getAccessToken();
//        if (!$accessToken) return false;
//
//        $requestUrl = $url .'?'. http_build_query($params);
//
//        Logger::debug("Making Live Connect request: " . $requestUrl);
//
//        $ch = curl_init($requestUrl);
//
//        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
//        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
//        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $accessToken));
//
//        try{
//            $response = curl_exec($ch);
//        }
//        catch (Exception $e)
//        {
//            Logger::error("Error connecting to Live Connect: " . $e->message . "(" . $e->code . ")");
//            return false;
//        }
//
//        $responseJson = json_decode($response);
//
//        if(is_object($responseJson) && property_exists($responseJson, 'error'))
//        {
//            Logger::error("Error during Live Connect request: " .
//                $responseJson->error . ' : ' . $responseJson->error_description);
//
//            return false;
//
//        } else {
//            return $responseJson;
//        }
//    }

    /**
    * Grab the access token, but authenticate first, useful for requests
    */
    public function getAccessToken()
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

    // -- basic getters & setters

    /**
    * See here for a full lsit of scopes and what they are used for:
    * http://msdn.microsoft.com/en-us/library/live/hh243646.aspx
    *
    * @todo allow adding via array and also add remove single scopes
    * @param string $scopes
    */
    public function setScopes($scopes)
    {
        $this->scopes = $scopes;
    }

    // -- Basic API interaction, post authentication

    /**
    * @param string $guid
    * @return object
    */
    public function getProfile($guid = 'me')
    {
        $client = new LiveRequest("https://apis.live.net/v5.0/" . $guid, $this->getAccessToken());

        return $client->get();
    }

    /**
    * @param array $params
    * @return object
    */
    public function getContacts($guid = 'me', $params = array())
    {
        $client = new LiveRequest("https://apis.live.net/v5.0/" . $guid . "/contacts", $this->getAccessToken());

        return $client->get();
    }
}