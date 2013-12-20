<?php

namespace Oro\Bundle\SidebarBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\SidebarBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Processor;

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
                'configs' => array(array()),
                'expected' => array('sidebar_widgets' => array())
            ),
            'full' => array(
                'configs' => array(
                    array(
                        'sidebar_widgets' => array(
                            'foo' => array(
                                'title' => 'Foo',
                                'icon' => 'icon.ico',
                                'module' => 'module'
                            )
                        )
                    )
                ),
                'expected' => array(
                    'sidebar_widgets' => array(
                        'foo' => array(
                            'title' => 'Foo',
                            'icon' => 'icon.ico',
                            'module' => 'module',
                            'settings' => null
                        )
                    )
                )
            ),
            'merge' => array(
                'configs' => array(
                    array(
                        'sidebar_widgets' => array(
                            'foo' => array(
                                'title' => 'Foo',
                                'icon' => 'icon.ico',
                                'module' => 'module'
                            )
                        )
                    ),
                    array(
                        'sidebar_widgets' => array(
                            'bar' => array(
                                'title' => 'Bar',
                                'icon' => 'icon2.ico',
                                'module' => 'module2',
                                'settings' => array('test1' => 1, 'test4' => 4)
                            )
                        )
                    ),
                    array(
                        'sidebar_widgets' => array(
                            'bar' => array(
                                'title' => 'Bar Extended',
                                'icon' => 'icon2_r.ico',
                                'module' => 'module2_r',
                                'settings' => array('test1' => 2, 'test2' => 3)
                            )
                        )
                    )
                ),
                'expected' => array(
                    'sidebar_widgets' => array(
                        'foo' => array(
                            'title' => 'Foo',
                            'icon' => 'icon.ico',
                            'module' => 'module',
                            'settings' => null
                        ),
                        'bar' => array(
                            'title' => 'Bar Extended',
                            'icon' => 'icon2_r.ico',
                            'module' => 'module2_r',
                            'settings' => array('test1' => 2, 'test2' => 3)
                        ),
                    )
                )
            )
        );
    }
}
