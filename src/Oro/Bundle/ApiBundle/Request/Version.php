<?php

namespace Oro\Bundle\ApiBundle\Request;

/**
 * A set of utility methods to work with API version.
 */
final class Version
{
    /** A string that can be used to reference the latest API version */
    public const LATEST = 'latest';

    /**
     * Normalizes the given API version.
     * If the given version is NULL, the "latest" string is returned as an API version.
     * If the given version number contains meaningless prefix, e.g. "v", it will be removed.
     */
    public static function normalizeVersion(?string $version): string
    {
        if (null === $version) {
            $version = self::LATEST;
        } elseif (0 === strncmp($version, 'v', 1)) {
            $version = substr($version, 1);
        }

        return $version;
    }
}
