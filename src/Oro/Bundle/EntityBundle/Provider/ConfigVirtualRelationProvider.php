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

        if (empty($this->items[$className])) {
            return [];
        }

        return isset($this->items[$className][$fieldName]);
    }

    /**
     * {@inheritdoc}
     */
    public function getVirtualRelationQuery($className, $fieldName)
    {
        $this->ensureVirtualFieldsInitialized();

        if (empty($this->items[$className][$fieldName]['query'])) {
            return [];
        }

        return $this->items[$className][$fieldName]['query'];
    }

    /**
     * {@inheritdoc}
     */
    public function getVirtualRelations($className)
    {
        $this->ensureVirtualFieldsInitialized();

        if (empty($this->items[$className])) {
            return [];
        }

        return $this->items[$className];
    }

    /**
     * {@inheritdoc}
     */
    public function getTargetJoinAlias($className, $fieldName)
    {
        $this->ensureVirtualFieldsInitialized();

        if (empty($this->items[$className][$fieldName])) {
            return null;
        }

        if (empty($this->items[$className][$fieldName]['target_join_alias'])) {
            return null;
        }

        return $this->items[$className][$fieldName]['target_join_alias'];
    }
}
