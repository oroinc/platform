<?php

namespace Oro\Bundle\EntityBundle\Provider;

class ConfigVirtualFieldProvider extends AbstractConfigVirtualProvider implements VirtualFieldProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function isVirtualField($className, $fieldName)
    {
        $this->ensureVirtualFieldsInitialized();

        return isset($this->items[$className][$fieldName]);
    }

    /**
     * {@inheritdoc}
     */
    public function getVirtualFieldQuery($className, $fieldName)
    {
        $this->ensureVirtualFieldsInitialized();

        return $this->items[$className][$fieldName]['query'];
    }

    /**
     * {@inheritdoc}
     */
    public function getVirtualFields($className)
    {
        $this->ensureVirtualFieldsInitialized();

        return isset($this->items[$className]) ? array_keys($this->items[$className]) : [];
    }
}
