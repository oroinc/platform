<?php

namespace Oro\Bundle\DistributionBundle\Error;

class ErrorHandler
{
    private $errorTypes = array(
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
    );

    /**
     * Register all custom application error handlers
     */
    public function registerHandlers()
    {
        set_error_handler(array($this, 'handleWarning'), E_WARNING | E_USER_WARNING);
        set_error_handler(array($this, 'handleError'), E_RECOVERABLE_ERROR | E_ERROR | E_USER_ERROR);
    }

    /**
     * @param int $number
     * @param string $string
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

    /**
     * @param int    $code
     * @param string $message
     * @param string $file
     * @param int    $line
     *
     * @return bool
     * @throws \ErrorException
     */
    public function handleError($code, $message, $file, $line)
    {
        /**
         * DateTimeZone produce error of E_ERROR type but it should be E_WARNING
         */
        if (strpos($message, 'DateTimeZone::__construct') !== false) {
            return false;
        }

        /**
         * Check if suppress warnings used
         */
        if (error_reporting() === 0) {
            return false;
        }

        $errorType = isset($this->errorTypes[$code]) ? $this->errorTypes[$code] : "Unknown error ({$code})";
        throw new \ErrorException("{$errorType}: {$message} in {$file} on line {$line}", 0, $code, $file, $line);
    }
}
