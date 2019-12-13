<?php

namespace Oro\Bundle\EntityConfigBundle\EventListener;

use Doctrine\Common\Cache\ClearableCache;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;

/**
 * Event listener that processed InvalidateTranslationCacheEvent event and clears the doctrine queries cache.
 * This allows to use translated enum options after saving them
 */
class InvalidateTranslationCacheListener
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function onInvalidateTranslationCache()
    {
        /** @var EntityManagerInterface $manager */
        $manager = $this->registry->getManagerForClass(AbstractEnumValue::class);
        $cacheProvider = $manager->getConfiguration()->getQueryCacheImpl();
        if ($cacheProvider instanceof ClearableCache) {
            $cacheProvider->deleteAll();
        }
    }
}
