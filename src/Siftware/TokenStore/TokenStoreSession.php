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

class TokenStoreSession extends TokenStore
{
    private $tokenStoreSessionVar;

    public function __construct()
    {
        session_start();

        $this->tokenStoreSessionVar = "swLiveConnect";

    }

    public function getTokens()
    {
        if (!isset($_SESSION[$this->tokenStoreSessionVar]))
        {
            $_SESSION[$this->tokenStoreSessionVar] = array();
        }
        return $_SESSION[$this->tokenStoreSessionVar];
    }

    // --

    public function saveTokens($tokens)
    {
        $saveTokens = array(
            'access_token' => $tokens->access_token,
            'refresh_token' => $tokens->refresh_token,
            'token_expires' => (time() + (int) $tokens->expires_in)
        );

        $_SESSION[$this->tokenStoreSessionVar] = $saveTokens;
        if (is_array($_SESSION[$this->tokenStoreSessionVar])) {
            return true;
        } else {
            return false;
        }
    }

    // --

    public function deleteTokens()
    {
        unset($_SESSION[$this->tokenStoreSessionVar]);
        if (!isset($_SESSION[$this->tokenStoreSessionVar])) {
            return true;
        } else {
            return false;
        }

    }

    /**
    * @param string path $storeName
    */
    public function setTokenStoreSessionVarName($storeName)
    {
        $this->tokenStoreSessionVar = $storeName;
    }
}