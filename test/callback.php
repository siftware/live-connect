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

require __DIR__ . "/bootstrap.php";

/**
* If Live Connect doesn't recognise the source of the request, (because no
* credentials are presented) the OAuth process is kicked off.
*
* Stage 1 is to ask the user to accept the connection then redirect to the callback
* URL (which is specified when signing up with Live Connect for the app) with the
* auth code in the query string. This is that callback URL.
*
* The authenticate() method below is OAuth stage 2. It passes the auth code back
* to Live Connect and hopefully receives both an authentication token and also a
* refresh token (as well as a token expiry date), the initiating user shouldn't
* need to visit here again unless:
*
* 1) they manually de-authorise your app
* 2) the tokens are no longer available
* 3) new scopes are needed
*/

$authCode = (isset($_GET["code"]) ? $_GET["code"] : "");

if (!$liveConnect->authenticate($authCode))
{
    print "Unable to authenticate against Live Connect";

    // clearly you'll want to handle this differently
    exit;
}
else
{
    // all good
    header("Location: /");
}