# Changelog

Note: Until there's a decent chunk of the API impelmented there won't be a release version.

 - 29/03/2014

        Initial release

 - 30/03/2014

        Added session storage option, made that default.
        Added Monolog
        Using Guzzle for API consumption, added a POST method & refactored key retrieval method
        Split out general request methods into separate class

 - 10/04/2014

        TokenStore now has a default
        Ability to supply custom TokenStorage class
        PSR-3 compatible logging
