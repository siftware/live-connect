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

// Bootstrapping, you'd normally put this all somewhere else, like an include.

require("./secret.php");

define ("LC_LOG_CHANNEL", "live-connect");

// this should write to your project root (from the test directory)
define ("LC_LOG_PATH", __DIR__ . "/../");
define ("LC_LOG_NAME", "live-connect.log");

//Useful debugging output, by default to a file (uses Monolog)
//Exceptions also go to the same place
define ("LC_DEBUG", true);

define("LIVE_CALLBACK_URL", "http://live-connect.dev/example.php");

require_once __DIR__ . '/../vendor/autoload.php';

use Siftware\LiveConnect;
use Siftware\LiveRequest;

$live = new LiveConnect(LIVE_CLIENT_ID, LIVE_CLIENT_SECRET, LIVE_CALLBACK_URL);

//See here for a full list of scopes and what they are used for:
//http://msdn.microsoft.com/en-us/library/live/hh243646.aspx
$live->setScopes("wl.offline_access,wl.signin,wl.basic,office.onenote_create");

// -- /end Bootstrapping

// This conditional is only needed when the request is for a new user.
// In production, catering for step 1 of the OAuth process (getting an auth code)
// could be all handled on the callback page.
//
// The rest of the time just use $this->getAuthToken() which will also handle auth/refresh for you
$authCode = (isset($_GET["code"]) ? $_GET["code"] : "");
if (!$live->authenticate($authCode))
{
    print "Unable to authenticate against Live Connect";
}
else
{
    print "<pre>";
    print_r(json_decode($live->getProfile()));
    print_r(json_decode($live->getContacts()));
    print "</pre>";
}