<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config\Stub;

use Oro\Bundle\ApiBundle\Config\Definition\ConfigurationSectionInterface;
use Oro\Bundle\ApiBundle\Config\Definition\ConfigurationSettingsInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

class TestConfiguration implements ConfigurationSectionInterface
{
    /**
     * {@inheritdoc}
     */
    public function configure(NodeBuilder $node): void
    {
        $node->end()->useAttributeAsKey('name')->prototype('variable');
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(string $section): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function setSettings(ConfigurationSettingsInterface $settings): void
    {
    }
}
