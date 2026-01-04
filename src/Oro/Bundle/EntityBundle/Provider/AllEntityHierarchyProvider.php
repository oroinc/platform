<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\ORM\ManagerBagInterface;
use Oro\Bundle\EntityBundle\ORM\OroClassMetadataFactory;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * This class allows to get parent entities/mapped superclasses for any entity.
 * If you interested configurable entities only {@see EntityHierarchyProvider}
 */
class AllEntityHierarchyProvider extends AbstractEntityHierarchyProvider
{
    public const HIERARCHY_METADATA_CACHE_KEY = 'oro_entity.all_hierarchy_metadata';

    /** @var ConfigProvider */
    protected $extendConfigProvider;

    /** @var ManagerBagInterface */
    protected $managerBag;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        ConfigProvider $extendConfigProvider,
        ManagerBagInterface $managerBag
    ) {
        parent::__construct($doctrineHelper);
        $this->extendConfigProvider = $extendConfigProvider;
        $this->managerBag = $managerBag;
    }

    #[\Override]
    protected function initializeHierarchy()
    {
        $managers = $this->managerBag->getManagers();
        foreach ($managers as $om) {
            $metadataFactory = $om->getMetadataFactory();
            $cache = $metadataFactory instanceof OroClassMetadataFactory
                ? $metadataFactory->getCacheItemPool()
                : null;
            if ($cache) {
                $hierarchyItem = $cache->getItem(static::HIERARCHY_METADATA_CACHE_KEY);
                if (!$hierarchyItem->isHit()) {
                    $hierarchy = $this->loadHierarchy($om);
                    $hierarchyItem->set($hierarchy);
                    $cache->save($hierarchyItem);
                } elseif (!empty($hierarchyItem->get())) {
                    $this->hierarchy = array_merge($this->hierarchy, $hierarchyItem->get());
                }
            } else {
                $this->loadHierarchy($om);
            }
        }
    }

    /**
     * @param ObjectManager $om
     *
     * @return array
     */
    protected function loadHierarchy(ObjectManager $om)
    {
        $hierarchy = [];

        $allMetadata = $this->doctrineHelper->getAllShortMetadata($om, false);
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
                $hierarchy[$metadata->name] = $parents;
                $this->hierarchy[$metadata->name] = $parents;
            }
        }

        return $hierarchy;
    }
}
