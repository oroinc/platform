<?php

namespace Oro\Bundle\NotificationBundle\EntityConfig;

use Oro\Bundle\EntityConfigBundle\EntityConfig\EntityConfigInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * Website aware entity configuration class.
 */
class WebsiteAwareEntityConfiguration implements EntityConfigInterface
{
    public function getSectionName(): string
    {
        return 'website';
    }

    public function configure(NodeBuilder $nodeBuilder): void
    {
        $nodeBuilder
            ->booleanNode('is_website_aware')
            ->info('`boolean` website is aware flag.')
            ->defaultFalse()
            ->end();
    }
}
