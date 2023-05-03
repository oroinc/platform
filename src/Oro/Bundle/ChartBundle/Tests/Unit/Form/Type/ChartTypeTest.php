<?php

namespace Oro\Bundle\ChartBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ChartBundle\Form\EventListener\ChartTypeEventListener;
use Oro\Bundle\ChartBundle\Form\Type\ChartSettingsType;
use Oro\Bundle\ChartBundle\Form\Type\ChartType;
use Oro\Bundle\ChartBundle\Model\ConfigProvider;
use Oro\Bundle\TestFrameworkBundle\Test\Form\MutableFormEventSubscriber;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class ChartTypeTest extends FormIntegrationTestCase
{
    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $configProvider;

    /** @var ChartType */
    private $type;

    protected function setUp(): void
    {
        $this->configProvider = $this->createMock(ConfigProvider::class);

        $this->type = new ChartType($this->configProvider);
        $this->type->setEventListener(
            new MutableFormEventSubscriber($this->createMock(ChartTypeEventListener::class))
        );

        parent::setUp();
    }

    /**
     * @dataProvider chartConfigsProvider
     */
    public function testBuildForm(array $chartConfigs)
    {
        $this->configProvider->expects($this->once())
            ->method('getChartNames')
            ->willReturn(array_keys($chartConfigs));
        $this->configProvider->expects($this->any())
            ->method('getChartConfig')
            ->willReturnCallback(function ($name) use ($chartConfigs) {
                return $chartConfigs[$name];
            });

        $form = $this->factory->create(ChartType::class);

        $this->assertTrue($form->has('name'));
        $this->assertTrue($form->has('settings'));

        foreach (array_keys($chartConfigs) as $chartName) {
            $this->assertTrue($form->get('settings')->has($chartName));
        }
    }

    public function chartConfigsProvider(): array
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
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension([
                $this->type,
                new ChartSettingsType($this->configProvider)
            ], [])
        ];
    }
}
