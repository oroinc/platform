<?php

namespace Oro\Bundle\AttachmentBundle\EntityConfig;

use Oro\Bundle\EntityConfigBundle\EntityConfig\EntityConfigInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * Provides validations entity config for attachment scope.
 */
class AttachmentEntityConfiguration implements EntityConfigInterface
{
    public function getSectionName(): string
    {
        return 'attachment';
    }

    public function configure(NodeBuilder $nodeBuilder): void
    {
        $nodeBuilder
            ->node('enabled', 'normalized_boolean')
                ->info('`boolean` indicates whether attachments can be added to the entity or not.')
                ->defaultFalse()
            ->end()
            ->scalarNode('maxsize')
                ->info('`integer` is the max size of the uploaded file in megabytes.')
                ->defaultValue(1)
            ->end()
            ->scalarNode('mimetypes')
                ->info('`string` is the list of all allowed MIME types for attachments. ' .
                    'MIME types are delimited by linefeed (n) symbol. ')
                ->example(['image/jpeg', 'image/gif', 'application/pdf'])
            ->end()
            ->node('immutable', 'normalized_boolean')
                ->info('`boolean` can be used to prohibit changing the attachment association state (regardless of ' .
                    'whether it is enabled or not) for the entity. If TRUE than the current state cannot be changed.')
            ->end()
        ;
    }
}
