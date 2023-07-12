<?php

namespace Oro\Bundle\DataAuditBundle\Strategy\Processor;

/**
 * This interface is for strategy processor classes that be run in series of data auditing message processors,
 * take specific strategy for entities before data auditing.
 */
interface EntityAuditStrategyProcessorInterface
{
    /**
     * This method supposes to be called in AuditChangedEntitiesProcessor,
     * it runs specific strategy for entities before data auditing standalone.
     */
    public function processChangedEntities(array $sourceEntityData): array;

    /**
     * This method supposes to be called in AuditChangedEntitiesInverseRelationsProcessor.
     * it runs specific strategy for entities that inverse the association between their parents.
     */
    public function processInverseRelations(array $sourceEntityData): array;

    /**
     * This method supposes to be called in AuditChangedEntitiesInverseCollectionsProcessor,
     * it runs specific strategy for collections that inverse the association between parent.
     */
    public function processInverseCollections(array $sourceEntityData): array;
}
