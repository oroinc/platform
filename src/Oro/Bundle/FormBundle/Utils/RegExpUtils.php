<?php

namespace Oro\Bundle\FormBundle\Utils;

/**
 * Contains handy functions for working with regular expressions.
 */
class RegExpUtils
{
    /**
     * Validates that regular expression syntax is valid by running it against an empty string.
     *
     * @param string $regexp
     * @return string|null Returns null is regular expression did not issue any errors.
     */
    public static function validateRegExp(string $regexp): ?string
    {
        $lastError = null;
        set_error_handler(static function (int $type, string $msg) use (&$lastError) {
            $lastError = $msg;
        });

        try {
            preg_match($regexp, '');
        } finally {
            restore_error_handler();
        }

        return $lastError;
    }
}
