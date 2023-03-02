<?php

namespace Oro\Bundle\ConfigBundle\Tests\Behat\Context;

trait NormalizeConfigOptionTrait
{
    private function normalizeConfigOptionValue(mixed $value): mixed
    {
        if ('true' === $value) {
            return true;
        }
        if ('false' === $value) {
            return false;
        }
        if (is_numeric($value)) {
            return (int)$value;
        }

        return $value;
    }
}
