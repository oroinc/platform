<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\Sorter;

use Oro\Bundle\DataGridBundle\Extension\Sorter\Configuration;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends \PHPUnit_Framework_TestCase
{
    public function testGetConfigTreeBuilder()
    {
        $configuration = new Configuration();
        $builder       = $configuration->getConfigTreeBuilder();

        $this->assertInstanceOf(TreeBuilder::class, $builder);
    }

    /**
     * @dataProvider processConfigurationDataProvider
     * @param array $configs
     * @param array $expected
     */
    public function testProcessConfiguration(array $configs, array $expected)
    {
        $configuration = new Configuration();
        $processor     = new Processor();
        $this->assertEquals($expected, $processor->processConfiguration($configuration, $configs));
    }

    /**
     * @return array
     */
    public function processConfigurationDataProvider()
    {
        return [
            'empty'                => [
                'configs'  => [[]],
                'expected' => [
                    Configuration::COLUMNS_KEY         => [],
                    Configuration::DEFAULT_SORTERS_KEY => [],
                ],
            ],
            'with all options set' => [
                'configs'  => [[
                    Configuration::COLUMNS_KEY                 => [],
                    Configuration::MULTISORT_KEY               => true,
                    Configuration::DEFAULT_SORTERS_KEY         => [],
                    Configuration::TOOLBAR_SORTING_KEY         => false,
                    Configuration::DISABLE_DEFAULT_SORTING_KEY => true,
                ]],
                'expected' => [
                    Configuration::COLUMNS_KEY                 => [],
                    Configuration::MULTISORT_KEY               => true,
                    Configuration::DEFAULT_SORTERS_KEY         => [],
                    Configuration::TOOLBAR_SORTING_KEY         => false,
                    Configuration::DISABLE_DEFAULT_SORTING_KEY => true,
                ]
            ]
        ];
    }
}
