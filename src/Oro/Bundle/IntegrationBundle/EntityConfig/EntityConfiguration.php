<?php

namespace Oro\Bundle\IntegrationBundle\EntityConfig;

use Oro\Bundle\EntityConfigBundle\EntityConfig\EntityConfigInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * Defines the entity configuration for Integration webhooks.
 */
class EntityConfiguration implements EntityConfigInterface
{
    #[\Override]
    public function getSectionName(): string
    {
        return 'integration';
    }

    #[\Override]
    public function configure(NodeBuilder $nodeBuilder): void
    {
        $nodeBuilder
            ->node('webhook_accessible', 'normalized_boolean')
                ->info('The `boolean` allows an entity to be exposed to webhooks.')
                ->defaultFalse()
            ->end()
            ->scalarNode('webhook_relations_includes')
                ->info(
                    '`string` representing relations should be included in the webhook payload.' .
                    ' Use JSON:API "include" parameter format.'
                )
            ->end();
    }
}
