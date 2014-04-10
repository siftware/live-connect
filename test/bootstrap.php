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

require __DIR__ . "/secret.php";

define("LC_CALLBACK_URL", "http://live-connect.dev/callback.php");

require_once __DIR__ . '/../vendor/autoload.php';

use Siftware\LiveConnect;
use Siftware\Logger;

/**
* PSR-3 compatible logger. Logs to file,  if you want to disable logging then just
* pass false as second paramter. See the class interface for more options.
* You can of course ditch this and pass in your own PS3-R logger instance
*/
$logger = new Logger(Psr\Log\LogLevel::DEBUG);

$liveConnect = new LiveConnect(LC_CLIENT_ID, LC_CLIENT_SECRET, LC_CALLBACK_URL, $logger);

/**
* See here for a full list of scopes and what they are used for:
* http://msdn.microsoft.com/en-us/library/live/hh243646.aspx
*/
$liveConnect->setScopes("wl.offline_access, wl.signin, wl.basic");