<?php

namespace Oro\Bundle\DraftBundle\Config\Validation;

use Oro\Bundle\EntityConfigBundle\Config\Validation\EntityConfigInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * Provides validations entity config for draft scope.
 */
class DraftEntityConfiguration implements EntityConfigInterface
{
    public function getSectionName(): string
    {
        return 'draft';
    }

    public function configure(NodeBuilder $nodeBuilder): void
    {
        $nodeBuilder
            ->node('draftable', 'normalized_boolean')
                ->info('`boolean` enables the “draft” functionality.')
            ->end()
        ;
    }
}
