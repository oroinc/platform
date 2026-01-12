<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Isolation;

/**
 * Generates unique tokens for test artifacts and temporary resources.
 *
 * This utility class creates unique identifiers using {@see \uniqid()} with a specified prefix,
 * useful for naming temporary files, screenshots, and other test-related resources.
 */
class TokenGenerator
{
    /**
     * @param string $prefix
     * @return string
     */
    public static function generateToken($prefix = 'p')
    {
        return str_replace('.', '', uniqid((string)$prefix, true));
    }
}
