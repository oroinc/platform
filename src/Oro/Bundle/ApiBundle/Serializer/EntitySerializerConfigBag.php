<?php

namespace Oro\Bundle\ApiBundle\Serializer;

use Oro\Bundle\ApiBundle\Versioning\VersionProviderInterface;

class EntitySerializerConfigBag implements VersionProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getVersions($className = null)
    {
        //@todo: must be implemented later
    }

    /**
     * Checks whether a config for the given class version exists
     *
     * @param string $className The FQCN of an entity
     * @param string $version   The version of a config
     *
     * @return bool
     */
    public function hasConfig($className, $version)
    {
        //@todo: must be implemented later
    }

    /**
     * Gets a config for the given class version
     *
     * @param string $className The FQCN of an entity
     * @param string $version   The version of a config
     *
     * @return array
     */
    public function getConfig($className, $version)
    {
        //@todo: must be implemented later
    }
}
