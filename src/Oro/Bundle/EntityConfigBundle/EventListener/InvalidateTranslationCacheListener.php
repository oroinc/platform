<?php

namespace Oro\Bundle\EntityConfigBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Symfony\Component\Cache\Adapter\AdapterInterface;

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

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function onInvalidateTranslationCache()
    {
        /** @var EntityManagerInterface $manager */
        $manager = $this->registry->getManagerForClass(AbstractEnumValue::class);
        $cacheProvider = $manager->getConfiguration()->getQueryCache();
        if ($cacheProvider instanceof AdapterInterface) {
            $cacheProvider->clear();
        }
    }
}
