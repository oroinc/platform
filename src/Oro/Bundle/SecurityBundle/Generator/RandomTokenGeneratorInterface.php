<?php

namespace Oro\Bundle\SecurityBundle\Generator;

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
