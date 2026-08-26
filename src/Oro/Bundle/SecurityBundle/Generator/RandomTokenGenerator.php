<?php

namespace Oro\Bundle\SecurityBundle\Generator;

/**
 * Generates cryptographically secure random tokens.
 */
class RandomTokenGenerator implements RandomTokenGeneratorInterface
{
    /**
     * {@inheritDoc}
     */
    public function generateToken($entropy = 256)
    {
        return self::generate($entropy);
    }

    public static function generate($entropy = 256): string
    {
        if (!\is_int($entropy) || $entropy < 8 || 0 !== $entropy % 8) {
            throw new \InvalidArgumentException('The token entropy must be a positive multiple of 8 bits.');
        }

        return bin2hex(random_bytes(intdiv($entropy, 8)));
    }
}
