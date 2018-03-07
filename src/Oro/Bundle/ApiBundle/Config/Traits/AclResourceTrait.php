<?php

namespace Oro\Bundle\ApiBundle\Config\Traits;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;

/**
 * Adds ACL resource related methods to a configuration class.
 *
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
     * Gets the name of ACL resource that should be used to protect the entity.
     *
     * @return string|null
     */
    public function getAclResource()
    {
        if (!array_key_exists(EntityDefinitionConfig::ACL_RESOURCE, $this->items)) {
            return null;
        }

        return $this->items[EntityDefinitionConfig::ACL_RESOURCE];
    }

    /**
     * Sets the name of ACL resource that should be used to protect the entity.
     *
     * @param string|null $aclResource
     */
    public function setAclResource($aclResource = null)
    {
        $this->items[EntityDefinitionConfig::ACL_RESOURCE] = $aclResource;
    }
}
