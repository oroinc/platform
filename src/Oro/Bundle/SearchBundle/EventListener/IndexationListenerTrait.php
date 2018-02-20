<?php

namespace Oro\Bundle\SearchBundle\EventListener;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\SearchBundle\Provider\AbstractSearchMappingProvider;

/**
 * This trait contains common code that repeats in
 * the listeners used for creating indexes.
 * @deprecated 1.1.0    IndexationListenerTrait is deprecated. Logic moved to the listener. Entities' fields to be
 *                      updated weren't translated into an array of fields to be indexed.
 */
trait IndexationListenerTrait
{
    /**
     * @var AbstractSearchMappingProvider
     */
    protected $mappingProvider;

    /**
     * @param UnitOfWork $uow
     *
     * @return object[]
     */
    protected function getEntitiesWithUpdatedIndexedFields(UnitOfWork $uow)
    {
        $entitiesToReindex = [];

        foreach ($uow->getScheduledEntityUpdates() as $hash => $entity) {
            $className = ClassUtils::getClass($entity);
            if (!$this->mappingProvider->hasFieldsMapping($className)) {
                continue;
            }
            $entitiesToReindex[$hash] = $entity;
        }

        return $entitiesToReindex;
    }
}
