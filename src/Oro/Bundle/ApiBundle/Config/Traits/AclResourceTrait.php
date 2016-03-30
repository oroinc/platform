<?php

namespace Oro\Bundle\ApiBundle\Config\Traits;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;

/**
 * @property array $items
 */
trait AclResourceTrait
{
    /**
     * Indicates whether the name of ACL resource is set explicitly.
     *
     * @return string
     */
    public function hasAclResource()
    {
        return array_key_exists(EntityDefinitionConfig::ACL_RESOURCE, $this->items);
    }

    /**
     * Gets the name of ACL resource.
     *
     * @return string|null
     */
    public function getAclResource()
    {
        return array_key_exists(EntityDefinitionConfig::ACL_RESOURCE, $this->items)
            ? $this->items[EntityDefinitionConfig::ACL_RESOURCE]
            : null;
    }

    /**
     * Sets the name of ACL resource.
     *
     * @param string|null $aclResource
     */
    public function setAclResource($aclResource = null)
    {
        $this->items[EntityDefinitionConfig::ACL_RESOURCE] = $aclResource;
    }
}
