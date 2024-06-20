<?php

namespace Oro\Bundle\EntityConfigBundle\EntityConfig;

use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * Provides validations entity config for entity_management scope.
 */
class EntityManagementConfig implements EntityConfigInterface
{
    public const SECTION = 'entity_management';

    public const OPTION = 'enabled';

    public function getSectionName(): string
    {
        return self::SECTION;
    }

    public function configure(NodeBuilder $nodeBuilder): void
    {
        $nodeBuilder
            ->node(self::OPTION, 'normalized_boolean')
                ->info('`boolean` enables the “entity management” functionality.')
                ->defaultTrue()
            ->end();
    }
}
