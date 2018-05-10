<?php

namespace Oro\Bundle\EntityExtendBundle\EventListener\Cache;

use Doctrine\ORM\Event\LifecycleEventArgs;

use Oro\Bundle\EntityExtendBundle\Cache\EnumTranslationCache;
use Oro\Bundle\EntityExtendBundle\Entity\EnumValueTranslation;

/**
 * Listen to updates of EnumValueTranslation and invalidate a cache
 */
class EnumValueTranslationListener
{
    /**
     * @var EnumTranslationCache
     */
    private $enumTranslationCache;

    /**
     * @param EnumTranslationCache $enumTranslationCache
     */
    public function __construct(EnumTranslationCache $enumTranslationCache)
    {
        $this->enumTranslationCache = $enumTranslationCache;
    }

    /**
     * @param EnumValueTranslation $entity
     * @param LifecycleEventArgs $args
     */
    public function postPersist(EnumValueTranslation $entity, LifecycleEventArgs $args)
    {
        $this->invalidateCache($entity);
    }

    /**
     * @param EnumValueTranslation $entity
     * @param LifecycleEventArgs $args
     */
    public function postUpdate(EnumValueTranslation $entity, LifecycleEventArgs $args)
    {
        $this->invalidateCache($entity);
    }

    /**
     * @param EnumValueTranslation $entity
     * @param LifecycleEventArgs $args
     */
    public function postRemove(EnumValueTranslation $entity, LifecycleEventArgs $args)
    {
        $this->invalidateCache($entity);
    }

    /**
     * @param EnumValueTranslation $entity
     */
    protected function invalidateCache(EnumValueTranslation $entity)
    {
        $this->enumTranslationCache->invalidate($entity->getObjectClass());
    }
}
