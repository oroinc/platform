<?php

namespace Oro\Bundle\EntityExtendBundle\EventListener\Cache;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Oro\Bundle\EntityExtendBundle\Cache\EnumTranslationCache;

/**
 * Abstract class for listeners which will invalidate a enum translations cache
 */
abstract class AbstractEnumValueListener
{
    /**
     * @var EnumTranslationCache
     */
    protected $enumTranslationCache;

    /**
     * @param EnumTranslationCache $enumTranslationCache
     */
    public function __construct(EnumTranslationCache $enumTranslationCache)
    {
        $this->enumTranslationCache = $enumTranslationCache;
    }

    /**
     * @param object $entity
     * @param LifecycleEventArgs $args
     */
    public function postPersist($entity, LifecycleEventArgs $args)
    {
        $this->invalidateCache($entity);
    }

    /**
     * @param object $entity
     * @param LifecycleEventArgs $args
     */
    public function postUpdate($entity, LifecycleEventArgs $args)
    {
        $this->invalidateCache($entity);
    }

    /**
     * @param object $entity
     * @param LifecycleEventArgs $args
     */
    public function postRemove($entity, LifecycleEventArgs $args)
    {
        $this->invalidateCache($entity);
    }

    /**
     * @param object $entity
     */
    abstract protected function invalidateCache($entity);
}
