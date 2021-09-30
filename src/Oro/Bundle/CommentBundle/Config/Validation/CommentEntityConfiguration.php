<?php

namespace Oro\Bundle\CommentBundle\Config\Validation;

use Oro\Bundle\EntityConfigBundle\Config\Validation\EntityConfigInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * Provides validations entity config for comment scope.
 */
class CommentEntityConfiguration implements EntityConfigInterface
{
    public function getSectionName(): string
    {
        return 'comment';
    }

    public function configure(NodeBuilder $nodeBuilder): void
    {
        $nodeBuilder
            ->node('enabled', 'normalized_boolean')
                ->info('`boolean` indicates whether the entity can have comments.')
            ->end()
            ->node('immutable', 'normalized_boolean')
                ->info('`boolean` is used to prohibit changing the comment association state (regardless of whether ' .
                    'it is enabled or not) for the entity. If TRUE, than the current state cannot be changed.')
            ->end()
        ;
    }
}
