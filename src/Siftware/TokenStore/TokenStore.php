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

/**
* Extend me
*
* @TODO make this concrete and use sessions by default?
*/
abstract class TokenStore
{
    public function __construct() {}
    public function getTokens() {}
    public function saveTokens() {}
    public function deleteTokens() {}
}