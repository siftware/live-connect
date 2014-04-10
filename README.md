# siftware/live-connect

A PHP package that consumes Microsoft's Live Connect REST API allowing OneDrive (SkyDrive) interaction and authentication.

Uses OAuth 2.0 authorization code grant flow as [documented here](http://msdn.microsoft.com/en-us/library/live/hh243647.aspx).

Most of the MS Live Connect examples use Javascript, [this](http://msdn.microsoft.com/en-us/library/live/hh243649.aspx) is the best resource I found for a general explanation of the Live Connect auth process on the server side.

## Install

Use Composer.

    cd && mkdir project-root && cd project-root

Create a file called composer.json, put this in it:

    {
        "require": {
            "siftware/live-connect": "dev-master"
        },
        "autoload": {
            "psr-4": {
                "Siftware\\": "src/Siftware"
            }
        }
    }

Install composer

    curl -s http://getcomposer.org/installer | php
    mv composer.phar composer

Grab the library

    ./composer install


## Usage

`test/bootstrap.php`

```php
<?php

/**
* Get these from https://account.live.com/developers/applications
*/
define("LC_CLIENT_ID", "<put yours here>");
define("LC_CLIENT_SECRET", "<put yours here>");

define("LC_CALLBACK_URL", "http://live-connect.dev/callback.php");

require_once __DIR__ . '/../vendor/autoload.php';

use Siftware\LiveConnect;
use Siftware\Logger;

/**
* PSR-3 compatible logger. Logs to file,  if you want to disable logging then just
* pass false as second parameter. See the class interface for more options.
* You can of course ditch this and pass in your own PS3-R logger instance
*/
$logger = new Logger(Psr\Log\LogLevel::DEBUG);

$liveConnect = new LiveConnect(LC_CLIENT_ID, LC_CLIENT_SECRET, LC_CALLBACK_URL, $logger);

/**
* See here for a full list of scopes and what they are used for:
* http://msdn.microsoft.com/en-us/library/live/hh243646.aspx
*/
$liveConnect->setScopes("wl.offline_access, wl.signin, wl.basic");
```

`test/callback.php`

```php
<?php

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
```

`test/index.php`

```php
<?php

require __DIR__ . "/bootstrap.php";

print "<pre>";

print_r(json_decode($liveConnect->getProfile()));
print_r(json_decode($liveConnect->getContacts()));

print "</pre>";
```

## To Do

I built this so I could start interacting with the OneNote API. So for now the only thing compelete is Authentication.

It will be relatively trivial to implement quite a few of the content retreival endpoints now, 2 have been supplied.

*Testing* needed.

If you end up using this as a base and flesh some more of the content methods out, feel free to submit a pull request.

## Author

I'm [Darren Beale](http://beale.rs) ([@bealers](http://twitter.com/bealers))

## Credit

 There are a couple of other OneDrive/LiveConnect classes that I have borrowed ideas from:

  - [Anuradha Jayathilaka's live-api-php-class](https://github.com/astroanu/live-api-php-class) which I couldn't get working
  - [Jonathan Lovatt's php-skydrive] (https://github.com/lovattj/php-skydrive) which I didn't try to get working but I did nick the TokenStore idea from

## Licence

 This package is released under the [MIT Licence](http://opensource.org/licenses/MIT)