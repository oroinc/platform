<?php

namespace Oro\Bundle\EntityConfigBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Symfony\Component\Cache\Adapter\AdapterInterface;

/**
 * Clears the doctrine queries cache after clearing of the translation cache.
 * This is required bo be able to use translated enum options after saving them.
 */
class InvalidateTranslationCacheListener
{
    private ManagerRegistry $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    public function onInvalidateDynamicTranslationCache(): void
    {
        /** @var EntityManagerInterface $em */
        $em = $this->doctrine->getManagerForClass(AbstractEnumValue::class);
        $cacheProvider = $em->getConfiguration()->getQueryCache();
        if ($cacheProvider instanceof AdapterInterface) {
            $cacheProvider->clear();
        }
    }
}
