<?php

namespace Oro\Bundle\DistributionBundle\Handler;

class ErrorHandler
{
    /**
     * @param int $errorTypes
     */
    public static function register($errorTypes)
    {
        set_error_handler(array(new static(), 'handleError'), $errorTypes);
    }

    /**
     * @param string $code
     * @param string $message
     * @param string $file
     * @param string $line
     *
     * @return bool
     * @throws \ErrorException
     */
    public static function handleError($code, $message, $file, $line)
    {
        /**
         * Check if suppress warnings used and error reporting level
         */
        if (!(error_reporting() & $code)) {
            return false;
        }

        throw new \ErrorException($message, 0, $code, $file, $line);
    }
}
