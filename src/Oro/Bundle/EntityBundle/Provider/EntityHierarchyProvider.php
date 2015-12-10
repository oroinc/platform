<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * This class allows to get parent entities/mapped superclasses for any configurable entity.
 * If you interested all entities {@see AllEntityHierarchyProvider}
 */
class EntityHierarchyProvider extends AbstractEntityHierarchyProvider
{
    /** @var ConfigProvider */
    protected $extendConfigProvider;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param ConfigProvider $extendConfigProvider
     */
    public function __construct(DoctrineHelper $doctrineHelper, ConfigProvider $extendConfigProvider)
    {
        parent::__construct($doctrineHelper);
        $this->extendConfigProvider = $extendConfigProvider;
    }

    /**
     * {@inheritdoc}
     */
    protected function initializeHierarchy()
    {
        $entityConfigs = $this->extendConfigProvider->getConfigs();
        foreach ($entityConfigs as $entityConfig) {
            if (ExtendHelper::isEntityAccessible($entityConfig)) {
                $className = $entityConfig->getId()->getClassName();
                $parents   = $this->loadParents($className);
                if (!empty($parents)) {
                    $this->hierarchy[$className] = $parents;
                }
            }
        }
    }
}
