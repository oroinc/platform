<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Isolation;

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
