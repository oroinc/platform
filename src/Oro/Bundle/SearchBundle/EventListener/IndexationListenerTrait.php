<?php

namespace Oro\Bundle\SearchBundle\EventListener;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\SearchBundle\Provider\SearchMappingProvider;

/**
 * This trait contains common code that repeats in
 * the listeners used for creating indexes.
 */
trait IndexationListenerTrait
{
    /**
     * @var SearchMappingProvider
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

            $entityConfig = $this->mappingProvider->getEntityConfig($className);

            $indexedFields = [];
            foreach ($entityConfig['fields'] as $fieldConfig) {
                $indexedFields[] = $fieldConfig['name'];
            }

            $changeSet = $uow->getEntityChangeSet($entity);
            $fieldsToReindex = array_intersect($indexedFields, array_keys($changeSet));
            if ($fieldsToReindex) {
                $entitiesToReindex[$hash] = $entity;
            }
        }

        return $entitiesToReindex;
    }
}
