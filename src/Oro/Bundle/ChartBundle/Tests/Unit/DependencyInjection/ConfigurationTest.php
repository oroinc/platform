<?php

namespace Oro\Bundle\ChartBundle\Tests\Unit\DependencyInjection;

use Symfony\Component\Config\Definition\Processor;

use Oro\Bundle\ChartBundle\DependencyInjection\Configuration;

class ConfigurationTest extends \PHPUnit_Framework_TestCase
{
    public function testGetConfigTreeBuilder()
    {
        $configuration = new Configuration();
        $builder = $configuration->getConfigTreeBuilder();

        $this->assertInstanceOf('Symfony\Component\Config\Definition\Builder\TreeBuilder', $builder);
    }

    /**
     * @dataProvider processConfigurationDataProvider
     */
    public function testProcessConfiguration($configs, $expected)
    {
        $configuration = new Configuration();
        $processor = new Processor();
        $this->assertEquals($expected, $processor->processConfiguration($configuration, $configs));
    }

    public function processConfigurationDataProvider()
    {
        return array(
            'empty' => array(
                'configs'  => array(
                    array(
                        'foo_chart' => array(
                            'label' => 'Foo',
                            'data_schema' => array(
                                array(
                                    'name' => 'label',
                                    'label' => 'Category (X axis)',
                                    'required' => true,
                                ),
                                array(
                                    'name' => 'value',
                                    'label' => 'Value (Y axis)',
                                    'required' => true,
                                ),
                            ),
                            'settings_schema' => array(
                                array(
                                    'name' => 'connect_dots_with_line',
                                    'label' => 'Connect line with dots',
                                    'type' => 'boolean',
                                ),
                                array(
                                    'name' => 'advanced_option',
                                    'label' => 'Advanced option',
                                    'type' => 'string',
                                    'options' => array(
                                        'foo' => 'bar'
                                    )
                                ),
                            ),
                            'data_transformer' => 'foo_data_transformer_service',
                            'template' => 'FooTemplate.html.twig'
                        )
                    ),
                    array(
                        'bar_chart' => array(
                            'label' => 'Bar',
                            'template' => 'BarTemplate.html.twig'
                        )
                    ),
                ),
                'expected' => array(
                    'foo_chart' => array(
                        'label' => 'Foo',
                        'data_schema' => array(
                            array(
                                'label' => 'Category (X axis)',
                                'name' => 'label',
                                'required' => true,
                                'filter' => []
                            ),
                            array(
                                'label' => 'Value (Y axis)',
                                'name' => 'value',
                                'required' => true,
                                'filter' => []
                            ),
                        ),
                        'settings_schema' => array(
                            array(
                                'name' => 'connect_dots_with_line',
                                'label' => 'Connect line with dots',
                                'type' => 'boolean',
                                'options' => array(),
                            ),
                            array(
                                'name' => 'advanced_option',
                                'label' => 'Advanced option',
                                'type' => 'string',
                                'options' => array(
                                    'foo' => 'bar'
                                ),
                            ),
                        ),
                        'data_transformer' => 'foo_data_transformer_service',
                        'template' => 'FooTemplate.html.twig',
                        'default_settings' => array(),
                    ),
                    'bar_chart' => array(
                        'label' => 'Bar',
                        'data_schema' => array(),
                        'settings_schema' => array(),
                        'default_settings' => array(),
                        'template' => 'BarTemplate.html.twig',
                    ),
                )
            )
        );
    }
}
