<?php

namespace Oro\Bundle\DistributionBundle\Error;

class ErrorHandler
{
    /**
     * Register all custom application error handlers
     */
    public function registerHandlers()
    {
        set_error_handler(array($this, 'handleWarning'), E_WARNING | E_USER_WARNING);
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
}
