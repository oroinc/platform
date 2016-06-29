<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config\Stub;

use Symfony\Component\Config\Definition\Builder\NodeBuilder;

use Oro\Bundle\ApiBundle\Config\Definition\ConfigurationSectionInterface;
use Oro\Bundle\ApiBundle\Config\Definition\ConfigurationSettingsInterface;

class TestConfiguration implements ConfigurationSectionInterface
{
    /**
     * {@inheritdoc}
     */
    public function configure(NodeBuilder $node)
    {
        $node->end()->useAttributeAsKey('name')->prototype('variable');
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable($section)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function setSettings(ConfigurationSettingsInterface $settings)
    {
    }
}
