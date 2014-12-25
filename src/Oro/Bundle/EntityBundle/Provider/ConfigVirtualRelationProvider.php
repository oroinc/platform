<?php

namespace Oro\Bundle\EntityBundle\Provider;

class ConfigVirtualRelationProvider extends AbstractConfigVirtualProvider implements VirtualRelationProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function isVirtualRelation($className, $fieldName)
    {
        $this->ensureVirtualFieldsInitialized();

        return isset($this->items[$className][$fieldName]);
    }

    /**
     * {@inheritdoc}
     */
    public function getVirtualRelationQuery($className, $fieldName)
    {
        $this->ensureVirtualFieldsInitialized();

        return $this->items[$className][$fieldName]['query'];
    }

    /**
     * {@inheritdoc}
     */
    public function getVirtualRelations($className)
    {
        $this->ensureVirtualFieldsInitialized();

        if (!isset($this->items[$className])) {
            return [];
        }

        return $this->items[$className];
    }
}
