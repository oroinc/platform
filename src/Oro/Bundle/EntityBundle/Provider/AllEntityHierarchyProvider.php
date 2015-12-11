<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\EntityManagerBag;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * This class allows to get parent entities/mapped superclasses for any entity.
 * If you interested configurable entities only {@see EntityHierarchyProvider}
 */
class AllEntityHierarchyProvider extends AbstractEntityHierarchyProvider
{
    /** @var ConfigProvider */
    protected $extendConfigProvider;

    /** @var EntityManagerBag */
    protected $entityManagerBag;

    /**
     * @param DoctrineHelper   $doctrineHelper
     * @param ConfigProvider   $extendConfigProvider
     * @param EntityManagerBag $entityManagerBag
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        ConfigProvider $extendConfigProvider,
        EntityManagerBag $entityManagerBag
    ) {
        parent::__construct($doctrineHelper);
        $this->extendConfigProvider = $extendConfigProvider;
        $this->entityManagerBag     = $entityManagerBag;
    }

    /**
     * {@inheritdoc}
     */
    protected function initializeHierarchy()
    {
        $entityManagers = $this->entityManagerBag->getEntityManagers();
        foreach ($entityManagers as $em) {
            /** @var ClassMetadata[] $allMetadata */
            $allMetadata = $em->getMetadataFactory()->getAllMetadata();
            foreach ($allMetadata as $metadata) {
                if ($metadata->isMappedSuperclass) {
                    continue;
                }
                if ($this->extendConfigProvider->hasConfig($metadata->name)
                    && !ExtendHelper::isEntityAccessible($this->extendConfigProvider->getConfig($metadata->name))
                ) {
                    continue;
                }
                $parents = $this->loadParents($metadata->name);
                if (!empty($parents)) {
                    $this->hierarchy[$metadata->name] = $parents;
                }
            }
        }
    }
}
