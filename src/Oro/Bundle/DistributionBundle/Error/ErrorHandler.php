<?php

namespace Oro\Bundle\DistributionBundle\Error;

use Symfony\Component\Debug\ErrorHandler as BaseErrorHandler;

/**
 * Handles php errors for next purposes:
 *  1. Don't show warning for php_network_getaddresses error due to BAP-3979
 *  2. Throw \ErrorException instead of fatal errors
 */
class ErrorHandler extends BaseErrorHandler
{
    /**
     * @var array
     */
    private $errorTypes = [
        E_WARNING => 'Warning',
        E_NOTICE => 'Notice',
        E_USER_ERROR => 'User Error',
        E_USER_WARNING => 'User Warning',
        E_USER_NOTICE => 'User Notice',
        E_STRICT => 'Runtime Notice',
        E_RECOVERABLE_ERROR => 'Catchable Fatal Error',
        E_DEPRECATED => 'Deprecated',
        E_USER_DEPRECATED => 'User Deprecated',
        E_ERROR => 'Error',
        E_CORE_ERROR => 'Core Error',
        E_COMPILE_ERROR => 'Compile Error',
        E_PARSE => 'Parse',
    ];

    /**
     * Register all custom application error handlers
     */
    public function registerHandlers()
    {
        $errorTypes = E_RECOVERABLE_ERROR | E_ERROR | E_USER_ERROR | E_WARNING | E_USER_WARNING;
        set_error_handler([$this, 'handleErrors'], $errorTypes);

        self::register($this, true);
    }

    /**
     * @param int    $code
     * @param string $message
     * @param string $file
     * @param int    $line
     *
     * @return bool
     * @throws \ErrorException
     */
    public function handleErrors($code, $message, $file, $line)
    {
        /**
         * Check if suppress warnings used
         */
        if (error_reporting() === 0) {
            return false;
        }

        switch ($code) {
            case E_WARNING:
            case E_USER_WARNING:
                return $this->handleWarning($code, $message);
                break;
            case E_ERROR:
            case E_USER_ERROR:
            case E_RECOVERABLE_ERROR:
                $errorType = isset($this->errorTypes[$code]) ? $this->errorTypes[$code] : "Unknown error ({$code})";
                $message = "{$errorType}: {$message} in {$file} on line {$line}";
                throw new \ErrorException($message, 0, $code, $file, $line);
                break;
        }

        return false;
    }

    /**
     * @param int $number
     * @param string $string
     * @deprecated since 1.7 it will be protected after 1.9
     *
     * @return bool
     */
    public function handleWarning($number, $string)
    {
        // silence warning from php_network_getaddresses due to https://magecore.atlassian.net/browse/BAP-3979
        if (strpos($string, 'php_network_getaddresses') !== false) {
            return true;
        }

        return false;
    }
}
