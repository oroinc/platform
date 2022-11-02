<?php

namespace Oro\Bundle\FeatureToggleBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\FeatureToggleBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Test Configuration
     */
    public function testGetConfigTreeBuilder()
    {
        $configuration = new Configuration();

        $this->assertInstanceOf(TreeBuilder::class, $configuration->getConfigTreeBuilder());
    }

    /**
     * @dataProvider processConfigurationDataProvider
     */
    public function testProcessConfiguration(array $configs, array $expected)
    {
        $configuration = new Configuration();
        $processor     = new Processor();

        $this->assertEquals($expected, $processor->processConfiguration($configuration, $configs));
    }

    public function processConfigurationDataProvider(): array
    {
        return [
            'empty' => [
                'configs'  => [[]],
                'expected' => [
                    'strategy' => 'unanimous',
                    'allow_if_all_abstain' => false,
                    'allow_if_equal_granted_denied' => true
                ]
            ]
        ];
    }
}
