<?php

namespace Oro\Bundle\DataAuditBundle\Strategy;

use Oro\Bundle\DataAuditBundle\Strategy\Processor\EntityAuditStrategyProcessorInterface;

/**
 * This class has all registered audit inverse strategy processors and return matched one for entity type.
 */
class EntityAuditStrategyDelegateProcessor implements EntityAuditStrategyProcessorInterface
{
    protected EntityAuditStrategyProcessorRegistry $registry;

    public function __construct(EntityAuditStrategyProcessorRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function processInverseCollections(array $sourceEntityData): array
    {
        $sourceEntityClass = $sourceEntityData['entity_class'] ?? "";

        return $this->registry->hasProcessor($sourceEntityClass)
            ? $this->registry->getProcessor($sourceEntityClass)->processInverseCollections($sourceEntityData)
            : $this->registry->getDefaultProcessor()->processInverseCollections($sourceEntityData);
    }

    public function processChangedEntities(array $sourceEntityData): array
    {
        $sourceEntityClass = $sourceEntityData['entity_class'] ?? "";

        return $this->registry->hasProcessor($sourceEntityClass)
            ? $this->registry->getProcessor($sourceEntityClass)->processChangedEntities($sourceEntityData)
            : $this->registry->getDefaultProcessor()->processChangedEntities($sourceEntityData);
    }

    public function processInverseRelations(array $sourceEntityData): array
    {
        $sourceEntityClass = $sourceEntityData['entity_class'] ?? "";

        return $this->registry->hasProcessor($sourceEntityClass)
            ? $this->registry->getProcessor($sourceEntityClass)->processInverseRelations($sourceEntityData)
            : $this->registry->getDefaultProcessor()->processInverseRelations($sourceEntityData);
    }
}
