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

require("./secret.php");

define("LIVE_CALLBACK_URL", "http://live-connect.dev/example.php");

require_once __DIR__ . '/../vendor/autoload.php';

use Siftware\LiveConnect;

$live = new LiveConnect(LIVE_CLIENT_ID, LIVE_CLIENT_SECRET, LIVE_CALLBACK_URL);

$live->setScopes("wl.offline_access,wl.signin,wl.basic,office.onenote_create");
$live->setDebug(true);

$authCode = (isset($_GET["code"]) ? $_GET["code"] : "");

if (!$live->authenticate($authCode))
{
    print "Unable to authenticate against Live Connect";
}
else
{
    print "<pre>";
    print_r($live->getProfile());
    print "</pre>";
}