<?php

namespace Oro\Bundle\DataAuditBundle\Config\Validation;

use Oro\Bundle\EntityConfigBundle\Config\Validation\EntityConfigInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * Provides validations entity config for dataaudit scope.
 */
class DataauditEntityConfiguration implements EntityConfigInterface
{
    public function getSectionName(): string
    {
        return 'dataaudit';
    }

    public function configure(NodeBuilder $nodeBuilder): void
    {
        $nodeBuilder
            ->node('auditable', 'normalized_boolean')
                ->info('`boolean` enables dataaudit for this entity. If it is not specified or set to false, you ' .
                    'can enable audit in the UI.')
            ->end()
            ->node('immutable', 'normalized_boolean')
                ->info('`boolean` this attribute can be used to prohibit changing the auditable state (no matter ' .
                    'whether it is enabled or not) for the entity. If TRUE than the current state cannot be changed.')
            ->end()
        ;
    }
}
