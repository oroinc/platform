<?php


namespace Oro\Bundle\ThemeBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\ThemeBundle\DependencyInjection\Configuration;
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
                'configs' => array(
                    array()
                ),
                'expected' => array(
                    'themes' => array()
                )
            ),
            'full' => array(
                'configs' => array(
                    array(
                        'active_theme' => 'foo',
                        'themes' => array(
                            'foo' => array(
                                'label' => 'Foo Theme',
                                'styles' => 'style.css',
                                'logo' => 'logo.png',
                                'icon' => 'favicon.ico',
                                'screenshot' => 'screenshot.png'
                            )
                        )
                    )
                ),
                'expected' => array(
                    'active_theme' => 'foo',
                    'themes' => array(
                        'foo' => array(
                            'label' => 'Foo Theme',
                            'styles' => array(
                                'style.css'
                            ),
                            'logo' => 'logo.png',
                            'icon' => 'favicon.ico',
                            'screenshot' => 'screenshot.png'
                        )
                    )
                )
            ),
            'merge' => array(
                'configs' => array(
                    array(
                        'active_theme' => 'foo',
                        'themes' => array(
                            'foo' => array(
                                'label' => 'Foo Theme',
                                'styles' => 'style.css',
                                'logo' => 'logo.png',
                                'icon' => 'favicon.ico',
                                'screenshot' => 'screenshot.png'
                            )
                        )
                    ),
                    array(
                        'active_theme' => 'bar',
                        'themes' => array(
                            'bar' => array(
                                'label' => 'Bar Theme',
                                'styles' => 'style.css',
                                'logo' => 'logo.png',
                                'icon' => 'favicon.ico',
                                'screenshot' => 'screenshot.png'
                            )
                        )
                    ),
                    array(
                        'themes' => array(
                            'bar' => array(
                                'label' => 'Bar Extended Theme',
                                'styles' => 'style-extended.css',
                                'logo' => 'logo-extended.png',
                                'icon' => 'favicon-extended.ico',
                                'screenshot' => 'screenshot-extended.png'
                            )
                        )
                    )
                ),
                'expected' => array(
                    'active_theme' => 'bar',
                    'themes' => array(
                        'foo' => array(
                            'label' => 'Foo Theme',
                            'styles' => array(
                                'style.css'
                            ),
                            'logo' => 'logo.png',
                            'icon' => 'favicon.ico',
                            'screenshot' => 'screenshot.png'
                        ),
                        'bar' => array(
                            'label' => 'Bar Extended Theme',
                            'styles' => array(
                                'style.css',
                                'style-extended.css'
                            ),
                            'logo' => 'logo-extended.png',
                            'icon' => 'favicon-extended.ico',
                            'screenshot' => 'screenshot-extended.png'
                        ),
                    )
                )
            )
        );
    }
}
