<?php

namespace Oro\Bundle\SecurityBundle\Generator;

/**
 * Generates cryptographically secure random tokens.
 *
 * This generator creates URI-safe secure tokens using the random_bytes function,
 * which provides cryptographically secure random data. The generated tokens are
 * suitable for use in security-sensitive contexts such as CSRF tokens, session tokens,
 * and other security-related identifiers.
 */
class RandomTokenGenerator implements RandomTokenGeneratorInterface
{
    #[\Override]
    public function generateToken($entropy = 256)
    {
        // Generate an URI safe base64 encoded string.
        $bytes = random_bytes($entropy / 8);

        return bin2hex($bytes);
    }
}
