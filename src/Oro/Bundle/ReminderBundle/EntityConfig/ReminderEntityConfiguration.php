<?php

namespace Oro\Bundle\ReminderBundle\EntityConfig;

use Oro\Bundle\EntityConfigBundle\EntityConfig\EntityConfigInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * Provides validations entity config for reminder scope.
 */
class ReminderEntityConfiguration implements EntityConfigInterface
{
    public function getSectionName(): string
    {
        return 'reminder';
    }

    public function configure(NodeBuilder $nodeBuilder): void
    {
        $nodeBuilder
            ->scalarNode('reminder_template_name')
                ->info('`string` reminder email template name.')
            ->end()
            ->scalarNode('reminder_flash_template_identifier')
                ->info('`string` reminder WebSocket message template name.')
            ->end()
        ;
    }
}
