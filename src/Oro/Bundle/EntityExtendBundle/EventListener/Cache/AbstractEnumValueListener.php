<?php

namespace Oro\Bundle\EntityExtendBundle\EventListener\Cache;

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
     */
    public function postPersist($entity)
    {
        $this->invalidateCache($entity);
    }

    /**
     * @param object $entity
     */
    public function postUpdate($entity)
    {
        $this->invalidateCache($entity);
    }

    /**
     * @param object $entity
     */
    public function postRemove($entity)
    {
        $this->invalidateCache($entity);
    }

    /**
     * @param object $entity
     */
    abstract protected function invalidateCache($entity);
}
