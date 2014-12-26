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

        $relations = array_filter(
            $this->items,
            function($item) use ($className, $fieldName) {
                if (empty($item[$fieldName])) {
                    return false;
                }

                return $item['related_entity_name'] === $className;
            }
        );

        return !empty($relations);
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
}
