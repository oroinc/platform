<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config\Stub;

use Symfony\Component\Config\Definition\Builder\NodeBuilder;

use Oro\Bundle\ApiBundle\Config\Definition\ConfigurationSectionInterface;

class TestConfiguration implements ConfigurationSectionInterface
{
    /**
     * {@inheritdoc}
     */
    public function configure(
        NodeBuilder $node,
        array $configureCallbacks,
        array $preProcessCallbacks,
        array $postProcessCallbacks
    ) {
        $node->end()->useAttributeAsKey('name')->prototype('variable');
    }

    public function isApplicable($section)
    {
        return true;
    }
}
