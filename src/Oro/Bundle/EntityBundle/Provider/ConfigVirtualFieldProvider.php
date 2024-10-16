<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Oro\Bundle\EntityBundle\Configuration\EntityConfiguration;
use Oro\Bundle\EntityBundle\Configuration\EntityConfigurationProvider;

/**
 * The provider for virtual fields defined in "Resources/config/oro/entity.yml" files.
 */
class ConfigVirtualFieldProvider extends AbstractConfigVirtualProvider implements VirtualFieldProviderInterface
{
    /** @var EntityConfigurationProvider */
    private $configProvider;

    public function __construct(
        EntityHierarchyProviderInterface $entityHierarchyProvider,
        EntityConfigurationProvider $configProvider
    ) {
        parent::__construct($entityHierarchyProvider);
        $this->configProvider = $configProvider;
    }

    #[\Override]
    public function isVirtualField($className, $fieldName)
    {
        $items = $this->getItems();

        return isset($items[$className][$fieldName]);
    }

    #[\Override]
    public function getVirtualFieldQuery($className, $fieldName)
    {
        $items = $this->getItems();

        return $items[$className][$fieldName]['query'];
    }

    #[\Override]
    public function getVirtualFields($className)
    {
        $items = $this->getItems();

        return isset($items[$className]) ? array_keys($items[$className]) : [];
    }

    #[\Override]
    protected function getConfiguration()
    {
        return $this->configProvider->getConfiguration(EntityConfiguration::VIRTUAL_FIELDS);
    }
}
