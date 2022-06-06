<?php

namespace Oro\Bundle\EmailBundle\EntityConfig;

use Oro\Bundle\EntityConfigBundle\EntityConfig\FieldConfigInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * Provides validations field config for email scope.
 */
class EmailFieldConfiguration implements FieldConfigInterface
{
    public function getSectionName(): string
    {
        return 'email';
    }

    public function configure(NodeBuilder $nodeBuilder): void
    {
        $nodeBuilder
            ->node('available_in_template', 'normalized_boolean')
                ->info('`boolean` if set to true, the field can be used in email templates.')
                ->defaultTrue()
            ->end()
            ->node('immutable', 'normalized_boolean')
                ->info('`boolean` this attribute can be used to prohibit changing the email state (no matter ' .
                    'whether it is enabled or not) for the entity. If TRUE than the current state cannot be changed.')
                ->defaultFalse()
            ->end()
        ;
    }
}
