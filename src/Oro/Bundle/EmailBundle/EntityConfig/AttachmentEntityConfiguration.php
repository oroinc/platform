<?php

namespace Oro\Bundle\EmailBundle\EntityConfig;

use Oro\Bundle\EntityConfigBundle\EntityConfig\EntityConfigInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * Provides validations entity config for attachment scope.
 */
class AttachmentEntityConfiguration implements EntityConfigInterface
{
    #[\Override]
    public function getSectionName(): string
    {
        return 'attachment';
    }

    #[\Override]
    public function configure(NodeBuilder $nodeBuilder): void
    {
        $nodeBuilder
            ->scalarNode('auto_link_attachments')
                ->info('`boolean` if TRUE, then Email Attachments are saved to the Attachment Entity.')
                ->defaultFalse()
            ->end()
        ;
    }
}
