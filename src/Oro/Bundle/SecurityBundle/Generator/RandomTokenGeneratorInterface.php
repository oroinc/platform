<?php

namespace Oro\Bundle\SecurityBundle\Generator;

/**
 * Defines the contract for generating cryptographically secure random tokens.
 *
 * Implementations of this interface are responsible for generating secure random
 * tokens suitable for security-sensitive operations such as CSRF protection,
 * session management, and other security-related use cases.
 */
interface RandomTokenGeneratorInterface
{
    /**
     * Generates a URI safe secure token.
     *
     * @param int $entropy The amount of entropy collected for a token (in bits)
     *
     * @return string
     */
    public function generateToken($entropy = 256);
}
