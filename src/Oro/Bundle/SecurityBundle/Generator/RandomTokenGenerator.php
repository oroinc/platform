<?php

namespace Oro\Bundle\SecurityBundle\Generator;

class RandomTokenGenerator implements RandomTokenGeneratorInterface
{
    /**
     * {@inheritDoc}
     */
    public function generateToken($entropy = 256)
    {
        // Generate an URI safe base64 encoded string.
        $bytes = random_bytes($entropy / 8);

        return bin2hex($bytes);
    }
}
