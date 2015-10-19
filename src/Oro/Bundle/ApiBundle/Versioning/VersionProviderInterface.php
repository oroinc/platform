<?php

namespace Oro\Bundle\ApiBundle\Versioning;

interface VersionProviderInterface
{
    /**
     * Gets all existing version numbers for the given class
     * or collects all existing version numbers for all classes
     *
     * @param string|null $className The FQCN of an entity
     *
     * @return string[]
     */
    public function getVersions($className = null);
}
