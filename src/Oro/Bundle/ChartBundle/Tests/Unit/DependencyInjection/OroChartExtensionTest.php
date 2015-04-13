<?php

namespace Oro\Bundle\ChartBundle\Tests\Unit\DependencyInjection;

use Oro\Component\Config\CumulativeResourceManager;
use Oro\Bundle\ChartBundle\DependencyInjection\OroChartExtension;

class OroChartExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OroChartExtension
     */
    protected $target;

    /**
     * @var array
     */
    protected $bundlesBackup;

    protected function setUp()
    {
        $this->bundlesBackup = CumulativeResourceManager::getInstance()->getBundles();

        $this->target = new OroChartExtension();
    }

    protected function tearDown()
    {
        CumulativeResourceManager::getInstance()->setBundles($this->bundlesBackup);
    }

    /**
     * @dataProvider loadDataProvider
     */
    public function testLoad(array $bundles, array $configs, array $expectedConfiguration)
    {
        $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerBuilder');

        CumulativeResourceManager::getInstance()->setBundles($bundles);
        $definition = $this->getMock('Symfony\Component\DependencyInjection\Definition');

        $definition->expects($this->once())
            ->method('replaceArgument')
            ->with(0, $expectedConfiguration);

        $container->expects($this->once())
            ->method('getDefinition')
            ->with('oro_chart.config_provider')
            ->will($this->returnValue($definition));
        $this->target->load($configs, $container);
    }

    public function loadDataProvider()
    {
        $firstBundle = 'Oro\Bundle\ChartBundle\Tests\Unit\Fixtures\FirstTestBundle\FirstTestBundle';
        $secondBundle = 'Oro\Bundle\ChartBundle\Tests\Unit\Fixtures\SecondTestBundle\SecondTestBundle';

        return array(
            array(
                'bundles' => array($firstBundle, $secondBundle),
                'configs' => array(
                    array(
                        'advanced_chart' => array(
                            'default_settings' => array('foo' => 'bar')
                        )
                    )
                ),
                'expectedConfiguration' => array(
                    'line_chart' => array(
                        'label' => 'Line Chart',
                        'data_schema' => array(
                            array(
                                'label' => 'Category (X axis)',
                                'name' => 'label',
                                'required' => true,
                                'type_filter' => [],
                                'default_type' => 'string'
                            ),
                            array(
                                'label' => 'Value (Y axis)',
                                'name' => 'value',
                                'required' => true,
                                'type_filter' => [],
                                'default_type' => 'string'
                            )
                        ),
                        'settings_schema' => array(
                            0 => array(
                                'name' => 'connect_dots_with_line',
                                'label' => 'Connect line with dots',
                                'type' => 'boolean',
                                'options' => array(
                                    'required' => true
                                )
                            )
                        ),
                        'default_settings' => array(
                            'chartColors' => array(
                                '#ACD39C', '#BE9DE2', '#6598DA', '#ECC87E', '#A4A2F6', '#6487BF', '#65BC87'
                            ),
                            'chartFontSize' => 9,
                            'chartFontColor' => '#454545',
                            'chartHighlightColor' => '#FF5E5E',
                        ),
                        'data_transformer' => 'some_service_id',
                        'template' => 'FirstTestBundle:Chart:lineChart.html.twig',
                        'xaxis' => array(
                            'mode' => 'normal',
                            'noTicks' => 5
                        )
                    ),
                    'advanced_chart' => array(
                        'label' => 'Advanced Chart (overridden)',
                        'template' => 'SecondTestBundle:Chart:advancedChart.html.twig',
                        'data_schema' => array(),
                        'settings_schema' => array(),
                        'default_settings' => array('foo' => 'bar', 'chartColors' => 'testColor'),
                        'xaxis' => array(
                            'mode' => 'normal',
                            'noTicks' => 5
                        )
                    )
                )
            )
        );
    }
}
