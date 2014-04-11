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
use Psr\Log\LoggerInterface;

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

    protected $logger;

    /**
    * @param mixed $liveId
    * @param mixed $liveSecret
    * @param mixed $redirectUrl
    * @param LoggerInterface $logger
    * @param mixed $store [File|Session (default)]
    *
    * @return LiveConnect
    */
    public function __construct($liveId, $liveSecret, $redirectUrl,
        LoggerInterface $logger, $store = "Session")
    {
        $this->liveId       = $liveId;
        $this->liveSecret   = $liveSecret;
        $this->redirectUrl  = $redirectUrl;

        // wl.offline also gives us access to the refresh token
        $this->scopes       = "wl.offline_access,wl.signin,wl.basic";

        $this->logger       = $logger;

        $this->tokenStore   = TokenStoreFactory::build($store);
    }

    // --

    /**
    * This allows you to roll your own Token Store, just implement Siftware\TokenStore
    *
    * @param TokenStore $store
    */
    public function setStore(TokenStore $store)
    {
        $this->tokenStore = $store;
    }

    /**
    * Multi-purpose authentication method, either kick off authentication
    * or refresh an expired token and add the new one to the store
    *
    * Use of this method is optional if you *know* that you have a valid refresh token.
    * It may be more useful at bootstrapping stage, on subsequent requests
    * $this->getAccessToken() method (that you'll need pass into the request) will also
    * check token expiry & refresh if needed
\    */
    public function authenticate($code = "")
    {
        $storedTokens = $this->tokenStore->getTokens();
        if ($storedTokens)
        {
            $expiry = $storedTokens['token_expires'];

            $this->logger->debug("Retrieved tokens from store, expiry: " . $expiry);

            // do we need a refresh?
            if (time() > (int) $expiry) {

                $authTokens = $this->getLiveTokens($storedTokens['refresh_token'], true);

            } else {

                return true;
            }

        } else {

            $this->logger->debug("No auth tokens stored");

            if ($code === "") {

                // The first step of the OAuth process, grab a code
                $liveAuthUrl = "https://login.live.com/oauth20_authorize.srf" .
                    "?response_type=code" .
                    "&client_id=" .  $this->liveId .
                    "&scope=" . $this->scopes .
                    "&redirect_uri=" . urlencode($this->redirectUrl);

                $this->logger->debug("Requesting an auth code to URL: " . $liveAuthUrl);

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

                $this->logger->debug("Saving tokens");
                return true;

            } else {

                $this->logger->error("Problem storing tokens");
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

        $client = new LiveRequest($authUrl, $this->logger);

        $this->logger->debug("Attempting to retrieve (" .
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
            $this->logger->error("Error getting access token from Live Connect: " .
                $responseJson->error . ' : ' . $responseJson->error_description);

            return false;

        } else {
            $this->logger->error("Unknown Error");
            return false;
        }
    }

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

    // -- Basic API interaction, post authentication, supplied as example only

    /**
    * @param string $guid
    * @return object
    */
    public function getProfile($guid = 'me')
    {
        $client = new LiveRequest("https://apis.live.net/v5.0/" . $guid,
            $this->logger, $this->getAccessToken());
        
        return $client->get();
    }

    /**
    * @param array $params
    * @return object
    */
    public function getContacts($guid = 'me', $params = array())
    {
        $client = new LiveRequest("https://apis.live.net/v5.0/" . $guid . "/contacts",
            $this->logger, $this->getAccessToken());
        
        return $client->get();
    }
}