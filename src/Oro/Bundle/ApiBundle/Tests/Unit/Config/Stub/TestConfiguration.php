<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config\Stub;

use Oro\Bundle\ApiBundle\Config\Definition\ConfigurationSectionInterface;
use Oro\Bundle\ApiBundle\Config\Definition\ConfigurationSettingsInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

class TestConfiguration implements ConfigurationSectionInterface
{
    #[\Override]
    public function configure(NodeBuilder $node): void
    {
        $node->end()->useAttributeAsKey('name')->prototype('variable');
    }

    #[\Override]
    public function isApplicable(string $section): bool
    {
        return true;
    }

    #[\Override]
    public function setSettings(ConfigurationSettingsInterface $settings): void
    {
    }
}
