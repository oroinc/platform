<?php
namespace Oro\Bundle\NavigationBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\NavigationBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\ArrayNode;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends \PHPUnit\Framework\TestCase
{
    public function testGetConfigTreeBuilder()
    {
        $configuration = new Configuration();
        $treeBuilder = $configuration->getConfigTreeBuilder();
        $this->assertInstanceOf(TreeBuilder::class, $treeBuilder);

        /** @var $root ArrayNode */
        $root = $treeBuilder->buildTree();
        $this->assertInstanceOf(ArrayNode::class, $root);
        $this->assertEquals('oro_navigation', $root->getName());
    }

    public function testProcessConfiguration()
    {
        $configuration = new Configuration();
        $processor     = new Processor();
        $expected = [
            'settings' => [
                'resolved' => true,
                'max_items' => [
                    'value' => 20,
                    'scope' => 'app'
                ],
                'title_suffix' => [
                    'value' => '',
                    'scope' => 'app'
                ],
                'title_delimiter' => [
                    'value' => '-',
                    'scope' => 'app'
                ],
                'breadcrumb_menu' => [
                    'value' => 'application_menu',
                    'scope' => 'app'
                ],
            ],
            'navigation_elements' => [],
            'titles' => [],
        ];

        $this->assertEquals($expected, $processor->processConfiguration($configuration, []));
    }
}
