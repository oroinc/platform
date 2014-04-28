<?php

namespace Oro\Bundle\ChartBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Oro\Bundle\ChartBundle\Form\Type\ChartType;
use Oro\Bundle\ChartBundle\Form\Type\ChartSettingsType;

class ChartTypeTest extends FormIntegrationTestCase
{
    /**
     * @var ChartType
     */
    protected $type;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $configProvider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $formBuilder;

    protected function setUp()
    {
        $this->configProvider = $this
            ->getMockBuilder('Oro\Bundle\ChartBundle\Model\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->type = new ChartType($this->configProvider);

        parent::setUp();
    }

    /**
     * @param array $chartConfigs
     *
     * @dataProvider chartConfigsProvider
     */
    public function testBuildForm($chartConfigs)
    {
        $this->configProvider
            ->expects($this->once())
            ->method('getChartConfigs')
            ->will($this->returnValue($chartConfigs));

        $form = $this->factory->create($this->type, null, []);

        $this->assertTrue($form->has('type'));

        foreach ($chartConfigs as $chartName => $chartConfig) {
            $this->assertTrue($form->has($chartName));
        }
    }

    /**
     * @param array $chartConfigs
     *
     * @dataProvider chartConfigsProvider
     */
    public function testSubmit($chartConfigs)
    {
        $this->configProvider
            ->expects($this->once())
            ->method('getChartConfigs')
            ->will($this->returnValue($chartConfigs));

        $form = $this->factory->create($this->type, null, []);

        $this->assertTrue($form->has('type'));

        foreach ($chartConfigs as $chartName => $chartConfig) {
            $this->assertTrue($form->has($chartName));
        }

        $form->submit(
            array_merge(
                ['type' => 'second'],
                ['second' => $chartConfigs['second']]
            )
        );

        $this->assertArrayHasKey('second', $form->getData());
        $this->assertArrayNotHasKey('first', $form->getData());
        $this->assertArrayNotHasKey('type', $form->getData());
    }

    /**
     * @return array
     */
    public function chartConfigsProvider()
    {
        return [
            'name' => [
                'chartConfigs' => [
                    'first'  => [
                        'label' => 'First',
                        'default_settings' => ['option' => 'value'],
                        'settings_schema' => [
                            'field' => [
                                'name' => 'name',
                                'type' => 'text'
                            ]
                        ]
                    ],
                    'second' => [
                        'label' => 'Second',
                        'default_settings' => ['option' => 'value2'],
                        'settings_schema' => [
                            'field' => [
                                'name' => 'name2',
                                'type' => 'text'
                            ]
                        ]
                    ]
                ],
            ]
        ];
    }

    protected function getExtensions()
    {
        $childType = new ChartSettingsType($this->configProvider);

        return [
            new PreloadedExtension(
                [
                    $childType->getName() => $childType,
                ],
                []
            )
        ];
    }
}
