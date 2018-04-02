<?php

namespace Oro\Bundle\ChartBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ChartBundle\Form\Type\ChartSettingsCollectionType;
use Oro\Bundle\ChartBundle\Form\Type\ChartSettingsType;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class ChartSettingsCollectionTypeTest extends FormIntegrationTestCase
{
    /**
     * @param array $chartConfigs
     *
     * @dataProvider chartConfigsProvider
     */
    public function testBuildForm($chartConfigs)
    {
        $form = $this->factory->create(ChartSettingsCollectionType::class, null, ['chart_configs' => $chartConfigs]);

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
                                'type'  => TextType::class
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
                                'type'  => TextType::class
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
