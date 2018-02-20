<?php

namespace Oro\Bundle\ApiBundle\Provider;

/**
 * Represents a storage for configuration of all registered Data API resources.
 */
interface ConfigBagInterface
{
    /**
     * Gets class names of all entities that have a configuration for the given version.
     *
     * @param string $version The version of a config
     *
     * @return string[]
     */
    public function getClassNames(string $version): array;

    /**
     * Gets a configuration for the given version of a class.
     *
     * @param string $className The FQCN of an entity
     * @param string $version   The version of a config
     *
     * @return array|null
     */
    public function getConfig(string $className, string $version): ?array;

    /**
     * Gets a relation configuration for the given version of a class.
     *
     * @param string $className The FQCN of an entity
     * @param string $version   The version of a config
     *
     * @return array|null
     */
    public function getRelationConfig(string $className, string $version): ?array;
}
