<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * Represents the configuration of API sub-resources.
 */
class SubresourcesConfig
{
    /** @var SubresourceConfig[] [association name => SubresourceConfig, ...] */
    private array $subresources = [];

    /**
     * Gets a native PHP array representation of the configuration.
     */
    public function toArray(): array
    {
        return ConfigUtil::convertObjectsToArray($this->subresources);
    }

    /**
     * Indicates whether there is a configuration at least one subresource.
     */
    public function isEmpty(): bool
    {
        return empty($this->subresources);
    }

    /**
     * Makes a deep copy of the object.
     */
    public function __clone()
    {
        $this->subresources = ConfigUtil::cloneObjects($this->subresources);
    }

    /**
     * Gets the configuration for all subresources.
     *
     * @return SubresourceConfig[] [association name => SubresourceConfig, ...]
     */
    public function getSubresources(): array
    {
        return $this->subresources;
    }

    /**
     * Gets the configuration of the subresource.
     */
    public function getSubresource(string $associationName): ?SubresourceConfig
    {
        return $this->subresources[$associationName] ?? null;
    }

    /**
     * Adds the configuration of the subresource.
     */
    public function addSubresource(string $associationName, SubresourceConfig $subresource = null): SubresourceConfig
    {
        if (null === $subresource) {
            $subresource = new SubresourceConfig();
        }

        $this->subresources[$associationName] = $subresource;

        return $subresource;
    }

    /**
     * Removes the configuration of the subresource.
     */
    public function removeSubresource(string $associationName): void
    {
        unset($this->subresources[$associationName]);
    }
}
