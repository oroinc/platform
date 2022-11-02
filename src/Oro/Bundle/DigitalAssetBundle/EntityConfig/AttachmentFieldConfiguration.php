<?php

namespace Oro\Bundle\DigitalAssetBundle\EntityConfig;

use Oro\Bundle\EntityConfigBundle\EntityConfig\FieldConfigInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * Provides validations field config for attachment scope.
 */
class AttachmentFieldConfiguration implements FieldConfigInterface
{
    public function getSectionName(): string
    {
        return 'attachment';
    }

    public function configure(NodeBuilder $nodeBuilder): void
    {
        $nodeBuilder
            ->node('use_dam', 'normalized_boolean')
                ->defaultFalse()
            ->end()
            ->node('acl_protected', 'normalized_boolean')
                ->defaultTrue()
            ->end()
        ;
    }
}
