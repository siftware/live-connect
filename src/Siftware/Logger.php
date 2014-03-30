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

namespace Siftware;

use Monolog\Logger as Monolog;
use Monolog\Handler\StreamHandler;

/**
*   Simple logger.
*
*   @todo http://www.php-fig.org/psr/psr-3/
*   @todo ability to use something other than Monolog\Handler\StreamHandler
*/
class Logger {

    private static $instance;

    /**
    * Set the logger handle if not already set.
    *
    * @todo allow runtime configuration of the level to log
    */
    private static function init()
    {
        if (!self::$instance)
        {
            $logger = new Monolog(LC_LOG_CHANNEL);

            try
            {
                $logger->pushHandler(new StreamHandler(LC_LOG_PATH . "/" . LC_LOG_NAME,
                  (LC_DEBUG ? Monolog::DEBUG : Monolog::NOTICE)));
            }
            // this is not working.
            catch (\UnexpectedValueException $e)
            {
                die("Error starting logger" . $e->getMessage());
            }

            self::$instance = $logger;
        }
    }

    /**
    * @param mixed $logPayload
    */
    public static function notice($logPayload)
    {
        self::init();

        self::$instance->addNotice($logPayload);
    }

    /**
    * @param mixed $logPayload
    */
    public static function error($logPayload)
    {
        self::init();

        self::$instance->addError($logPayload);
    }

    /**
    * @param mixed $logPayload
    */
    public static function debug($logPayload)
    {
        self::init();

        self::$instance->addDebug($logPayload);
    }

}