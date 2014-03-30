#siftware/live-connect

A PHP package that consumes Microsoft's Live Connect REST API allowing OneDrive (SkyDrive) interaction and authentication.

Uses OAuth 2.0 authorization code grant flow as [documented here](http://msdn.microsoft.com/en-us/library/live/hh243647.aspx).

Most of the MS Live Connect examples use Javascript, [this](http://msdn.microsoft.com/en-us/library/live/hh243649.aspx) is the best resource I found for a general explanation of the Live Connect auth process on the server side.

##Install

Use Composer.

    cd && mkdir project-root && cd project-root
    echo "
    {
        "require": {
            "siftware/live-connect": "dev-master"
        },
        "autoload": {
            "psr-4": {
                "Siftware\\": "src/Siftware"
            }
        }
    }" > composer.json

    curl -s http://getcomposer.org/installer | php
    php composer.phar install
    ./composer install


##Usage

see `test/example.php`

Note how the auth code we get from step one is being checked for in the example. We have to look out for this only for the very first time we authenticate. So, in production it might be a better idea to have the aunthenticate conditional on a dedicated callback page and rely on the authentication check within `liveRequest()`.

There is some rudimentary debugging that currently spits out to your webserver's error log, this is easily extendable.

##To Do

I built this so I could start interacting with the OneNote API. So for now the only thing compelete is Authentication.

It will be relatively trivial to implement quite a bit of the content retreival endpoints, for POST/PUT & DELETE more work will be needed.

*Testing* needed.

If you end up using this as a base and flesh some more of the content methods out feel free to submit a pull request.

##Author

I'm [Darren Beale](http://beale.rs) ([@bealers](http://twitter.com/bealers))

##Credit

 There are a couple of other OneDrive/LiveConnect classes that I have borrowed ideas from:

  - [Anuradha Jayathilaka's live-api-php-class](https://github.com/astroanu/live-api-php-class) which I couldn't get working
  - [Jonathan Lovatt's php-skydrive] (https://github.com/lovattj/php-skydrive) which I didn't try to get working but I did nick the TokenStore idea from

##Licence

 This package is released under the [MIT Licence](http://opensource.org/licenses/MIT)