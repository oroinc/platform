<?php

namespace Oro\Bundle\LocaleBundle\Strategy\Processor;

use Oro\Bundle\DataAuditBundle\Strategy\Processor\DefaultEntityAuditStrategyProcessor;

/**
 * For entity to find relationship with edited parent entity.
 * LocalizedFallbackValue will find from a map of all entity extends AbstractLocalizedFallbackValue
 */
class AbstractLocalizedAuditStrategyProcessor extends DefaultEntityAuditStrategyProcessor
{
    /**
     * Skip AbstractLocalizedFallbackValue when auditing...
     * @param array $sourceEntityData
     * @return array
     */
    public function processChangedEntities(array $sourceEntityData): array
    {
        return [];
    }

    /**
     * Skip AbstractLocalizedFallbackValue when auditing...
     * @param array $sourceEntityData
     * @return array
     */
    public function processInverseRelations(array $sourceEntityData): array
    {
        return [];
    }
}
