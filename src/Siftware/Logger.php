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
use Monolog\Handler\NullHandler;
use Psr\Log\LogLevel;
use Psr\Log\LoggerInterface;

/**
*   Throwaway PSR-3 compatible logger, wraps Monolog
**
*   You probably want to ditch this and use your own.
*
*   @todo https://github.com/guzzle/log-subscriber
*   @todo setters
*/
class Logger implements LoggerInterface
{
    private $logger;

    /**
    * Defaults are designed to facilitate minimal bootstrap code
    *
    * @param bool $disable
    * @param mixed $level
    * @param string $channel
    * @param string $path
    * @param string $filename
    * @return Monolog
    */
    public function __construct(
        $level = LogLevel::NOTICE,
        $enable = true,
        $path = "/tmp",
        $filename = "live-connect.log",
        $channel = "live-connect")
    {
        if ($channel === "") {
            $channel = "live-connect";
        }

        if ($path === "") {
            $path = "/tmp";
        }

        if ($filename === "") {
            $filename = "live-connect.log";
        }

        if (!$enable) {
            $handler = new NullHandler();
        } else {
            $handler = new StreamHandler($path . "/" . $filename, $level);
       }

        try
        {
            $this->logger = new Monolog($channel);
            $this->logger->pushHandler($handler);
        }
        // this is not working.
        catch (\UnexpectedValueException $e)
        {
            die("Error starting logger" . $e->getMessage());
        }

        //return $this->logger;
    }


    /**
     * System is unusable.
     *
     * @param string $message
     * @param array $context
     * @return null
     */
    public function emergency($message, array $context = array())
    {
        $this->logger->emergency($message, $context);
    }

    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     *
     * @param string $message
     * @param array $context
     * @return null
     */
    public function alert($message, array $context = array())
    {
        $this->logger->alert($message, $context);
    }

    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     *
     * @param string $message
     * @param array $context
     * @return null
     */
    public function critical($message, array $context = array())
    {
        $this->logger->critical($message, $context);
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param string $message
     * @param array $context
     * @return null
     */
    public function error($message, array $context = array())
    {
        $this->logger->error($message, $context);
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param string $message
     * @param array $context
     * @return null
     */
    public function warning($message, array $context = array())
    {
        $this->logger->warning($message, $context);
    }

    /**
     * Normal but significant events.
     *
     * @param string $message
     * @param array $context
     * @return null
     */
    public function notice($message, array $context = array())
    {
        $this->logger->notice($message, $context);
    }

    /**
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     *
     * @param string $message
     * @param array $context
     * @return null
     */
    public function info($message, array $context = array())
    {
        $this->logger->info($message, $context);
    }

    /**
     * Detailed debug information.
     *
     * @param string $message
     * @param array $context
     * @return null
     */
    public function debug($message, array $context = array())
    {
        $this->logger->debug($message, $context);
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     * @return null
     */
    public function log($level, $message, array $context = array())
    {
        $this->logger->log($level, $message, $context);
    }

}