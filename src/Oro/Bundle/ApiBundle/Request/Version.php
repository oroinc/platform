<?php

namespace Oro\Bundle\ApiBundle\Request;

/**
 * API version related constants.
 */
final class Version
{
    /** a string that can be used to reference the latest API version */
    const LATEST = 'latest';

    /**
     * Normalizes a given API version.
     * If the given version is NULL, the "latest" version will be returned.
     * If the given version number contains meaningless prefix, e.g. "v", it will be removed.
     *
     * @param string|null $version
     *
     * @return string
     */
    public static function normalizeVersion($version)
    {
        if (null === $version) {
            $version = Version::LATEST;
        } elseif (0 === strpos($version, 'v')) {
            $version = substr($version, 1);
        }

        return $version;
    }
}
