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

namespace Siftware\TokenStore;

class TokenStoreFile implements TokenStore
{
    private $tokenStoreLocation;

    public function __construct()
    {
        // this might not work for you, if so use the setter
        $this->tokenStoreLocation = "/tmp/swLiveTokens";
    }

    public function getTokens()
    {
        return json_decode(file_get_contents($this->tokenStoreLocation), true);
    }

    // --

    public function saveTokens($tokens)
    {
        $saveTokens = array(
            'access_token' => $tokens->access_token,
            'refresh_token' => $tokens->refresh_token,
            'token_expires' => (time() + (int) $tokens->expires_in)
        );

        if (file_put_contents($this->tokenStoreLocation, json_encode($saveTokens))) {
            return true;
        } else {
            return false;
        }
    }

    // --

    public function deleteTokens()
    {
        if (file_put_contents($this->tokenStoreLocation, "--")) {
            return true;
        } else {
            return false;
        }

    }

    /**
    * Override the default storage location
    *
    * @param string path $store
    */
    public function setTokenStore($store)
    {
        $this->tokenStoreLocation = $store;
    }
}