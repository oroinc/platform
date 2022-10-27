<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\DataFixtures\Stub\Extension;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public const ROOT = 'someExtensionConfig';
    public const NODE = 'someKey';

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder(self::ROOT);

        $builder->getRootNode()->children()
                ->scalarNode(self::NODE)->end()
            ->end();

        return $builder;
    }
}
