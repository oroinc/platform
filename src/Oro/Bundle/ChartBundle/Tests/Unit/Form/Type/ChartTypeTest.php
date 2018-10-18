<?php

namespace Oro\Bundle\ChartBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ChartBundle\Form\Type\ChartSettingsType;
use Oro\Bundle\ChartBundle\Form\Type\ChartType;
use Oro\Bundle\TestFrameworkBundle\Test\Form\MutableFormEventSubscriber;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class ChartTypeTest extends FormIntegrationTestCase
{
    /**
     * @var ChartType
     */
    protected $type;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $configProvider;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $formBuilder;

    protected function setUp()
    {
        $this->configProvider = $this
            ->getMockBuilder('Oro\Bundle\ChartBundle\Model\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $mock = $this
            ->getMockBuilder('Oro\Bundle\ChartBundle\Form\EventListener\ChartTypeEventListener')
            ->getMock();

        $eventListener = new MutableFormEventSubscriber($mock);

        $this->type = new ChartType($this->configProvider);
        $this->type->setEventListener($eventListener);

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

        $form = $this->factory->create(ChartType::class, null, []);

        $this->assertTrue($form->has('name'));
        $this->assertTrue($form->has('settings'));

        foreach (array_keys($chartConfigs) as $chartName) {
            $this->assertTrue($form->get('settings')->has($chartName));
        }
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
                        'label'            => 'First',
                        'default_settings' => ['option' => 'value'],
                        'settings_schema'  => [
                            'field' => [
                                'name'  => 'name',
                                'label' => 'Name',
                                'type'  => TextType::class,
                            ]
                        ],
                        'data_schema'      => []
                    ],
                    'second' => [
                        'label'            => 'Second',
                        'default_settings' => ['option' => 'value2'],
                        'settings_schema'  => [
                            'field' => [
                                'name'  => 'name2',
                                'label' => 'Name2',
                                'type'  => TextType::class,
                            ]
                        ],
                        'data_schema'      => [
                            'option' => 'value'
                        ]
                    ]
                ],
            ]
        ];
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $childType = new ChartSettingsType($this->configProvider);

        return [
            new PreloadedExtension(
                [
                    $this->type,
                    ChartSettingsType::class => $childType
                ],
                []
            )
        ];
    }
}
