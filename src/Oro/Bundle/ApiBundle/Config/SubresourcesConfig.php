<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class SubresourcesConfig
{
    /** @var SubresourceConfig[] [association name => SubresourceConfig, ...] */
    protected $subresources = [];

    /**
     * Gets a native PHP array representation of the configuration.
     *
     * @return array
     */
    public function toArray()
    {
        return ConfigUtil::convertObjectsToArray($this->subresources);
    }

    /**
     * Indicates whether there is a configuration at least one subresource.
     *
     * @return bool
     */
    public function isEmpty()
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
    public function getSubresources()
    {
        return $this->subresources;
    }

    /**
     * Gets the configuration of the subresource.
     *
     * @param string $associationName
     *
     * @return SubresourceConfig|null
     */
    public function getSubresource($associationName)
    {
        return isset($this->subresources[$associationName])
            ? $this->subresources[$associationName]
            : null;
    }

    /**
     * Adds the configuration of the subresource.
     *
     * @param string                 $associationName
     * @param SubresourceConfig|null $subresource
     *
     * @return SubresourceConfig
     */
    public function addSubresource($associationName, SubresourceConfig $subresource = null)
    {
        if (null === $subresource) {
            $subresource = new SubresourceConfig();
        }

        $this->subresources[$associationName] = $subresource;

        return $subresource;
    }

    /**
     * Removes the configuration of the subresource.
     *
     * @param string $associationName
     */
    public function removeSubresource($associationName)
    {
        unset($this->subresources[$associationName]);
    }
}
