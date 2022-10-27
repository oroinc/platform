<?php

namespace Oro\Bundle\SecurityBundle\Tools;

/**
 * Checks if UUID is valid.
 */
class UUIDValidator
{
    public static function isValidV4(string $uuid): bool
    {
        return $uuid && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[4][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $uuid);
    }
}
