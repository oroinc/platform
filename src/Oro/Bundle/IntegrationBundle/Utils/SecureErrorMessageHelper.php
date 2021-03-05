<?php

namespace Oro\Bundle\IntegrationBundle\Utils;

/**
 * Sanitizes security information in a message string
 */
class SecureErrorMessageHelper
{
    /**
     * Sanitize error message for secure info
     *
     * @param string $message
     *
     * @return string
     */
    public static function sanitizeSecureInfo($message)
    {
        if (is_string($message)) {
            $message = preg_replace('#(<apiKey.*?>)(.*)(</apiKey>)#i', '$1***$3', $message);
            return preg_replace('#(<ns1:loginParam/><param1>)(.*)(</param1>)#i', '$1***$3', $message);
        }

        return $message;
    }
}
