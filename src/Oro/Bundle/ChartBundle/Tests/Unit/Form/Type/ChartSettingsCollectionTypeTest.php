<?php

namespace Oro\Bundle\ChartBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Oro\Bundle\ChartBundle\Form\Type\ChartSettingsCollectionType;
use Oro\Bundle\ChartBundle\Form\Type\ChartSettingsType;

class ChartSettingsCollectionTypeTest extends FormIntegrationTestCase
{
    /**
     * @var ChartSettingsCollectionType
     */
    protected $type;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $configProvider;

    protected function setUp()
    {
        $this->type = new ChartSettingsCollectionType();

        parent::setUp();
    }

    /**
     * @param array $chartConfigs
     *
     * @dataProvider chartConfigsProvider
     */
    public function testBuildForm($chartConfigs)
    {
        $form = $this->factory->create($this->type, null, ['chart_configs' => $chartConfigs]);

        foreach (array_keys($chartConfigs) as $chartName) {
            $this->assertTrue($form->has($chartName));
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
                                'type'  => 'text'
                            ]
                        ]
                    ],
                    'second' => [
                        'label'            => 'Second',
                        'default_settings' => ['option' => 'value2'],
                        'settings_schema'  => [
                            'field' => [
                                'name'  => 'name2',
                                'label' => 'Name2',
                                'type'  => 'text'
                            ]
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
        $configProvider = $this
            ->getMockBuilder('Oro\Bundle\ChartBundle\Model\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $childType = new ChartSettingsType($configProvider);

        return [
            new PreloadedExtension(
                [
                    $childType->getName() => $childType
                ],
                []
            )
        ];
    }
}
