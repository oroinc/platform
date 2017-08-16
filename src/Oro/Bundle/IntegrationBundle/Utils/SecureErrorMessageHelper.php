<?php

namespace Oro\Bundle\IntegrationBundle\Utils;

class SecureErrorMessageHelper
{
    /**
     * Sanitize error message for secure info
     *
     * @param string
     *
     * @return string
     */
    public static function sanitizeSecureInfo($message)
    {
        if (is_string($message)) {
            return preg_replace('#(<apiKey.*?>)(.*)(</apiKey>)#i', '$1***$3', $message);
        }

        return $message;
    }
}
