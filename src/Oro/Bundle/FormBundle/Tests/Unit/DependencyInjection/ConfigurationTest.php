<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\DependencyInjection;

use Symfony\Component\Config\Definition\ArrayNode;
use Symfony\Component\Config\Definition\Processor;

use Oro\Bundle\FormBundle\DependencyInjection\Configuration;

class ConfigurationTest extends \PHPUnit_Framework_TestCase
{
    public function testGetConfigTreeBuilder()
    {
        $configuration = new Configuration();
        $builder = $configuration->getConfigTreeBuilder();
        $this->assertInstanceOf('Symfony\Component\Config\Definition\Builder\TreeBuilder', $builder);

        /** @var $root ArrayNode */
        $root = $builder->buildTree();
        $this->assertInstanceOf('Symfony\Component\Config\Definition\ArrayNode', $root);
        $this->assertEquals('oro_form', $root->getName());
    }

    public function testProcessConfiguration()
    {
        $processor = new Processor();

        $this->assertEquals(
            [
                'settings' => [
                    'resolved' => true,
                    'wysiwyg_enabled' => [
                        'value' => true,
                        'scope' => 'app'
                    ]
                ]
            ],
            $processor->processConfiguration(new Configuration(), [])
        );
    }
}
