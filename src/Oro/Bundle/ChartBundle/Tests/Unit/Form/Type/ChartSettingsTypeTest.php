<?php

namespace Oro\Bundle\ChartBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ChartBundle\Form\Type\ChartSettingsType;
use Oro\Bundle\ChartBundle\Model\ConfigProvider;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class ChartSettingsTypeTest extends FormIntegrationTestCase
{
    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $configProvider;

    /** @var ChartSettingsType */
    private $type;

    protected function setUp(): void
    {
        $this->configProvider = $this->createMock(ConfigProvider::class);

        $this->type = new ChartSettingsType($this->configProvider);

        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension([$this->type], [])
        ];
    }

    /**
     * @dataProvider invalidOptionsProvider
     */
    public function testRequireOptionsIncorrectType(array $options, string $exception, string $message)
    {
        $this->expectException($exception);
        $this->expectExceptionMessage($message);

        $this->factory->create(ChartSettingsType::class, null, $options);
    }

    public function invalidOptionsProvider(): array
    {
        return [
            'name'         => [
                'options'   => ['chart_name' => 11],
                'exception' => InvalidOptionsException::class,
                'message'   => 'The option "chart_name" with value 11 is expected to be of type "string", '
                    . 'but is of type "int".'
            ],
            'chart_config' => [
                'options'   => ['chart_name' => 'test', 'chart_config' => 11],
                'exception' => InvalidOptionsException::class,
                'message'   => 'The option "chart_config" with value 11 is expected to be of type "array", '
                    . 'but is of type "int".'
            ],
            'empty'        => [
                'options'   => [],
                'exception' => MissingOptionsException::class,
                'message'   => 'The required option "chart_name" is missing.'
            ]
        ];
    }

    /**
     * @dataProvider fieldDataProvider
     */
    public function testFieldAdded(array $fieldsData, string $chartName, bool $useParentOptions)
    {
        $chartOptions = array_merge(
            ['chart_name' => $chartName],
            ['settings_schema' => $fieldsData]
        );

        $formOptions = ['chart_name' => $chartName];
        if ($useParentOptions) {
            $formOptions['chart_config'] = $chartOptions;
        }

        $this->configProvider->expects($this->any())
            ->method('getChartConfig')
            ->with($chartName)
            ->willReturn($chartOptions);

        $form = $this->factory->create(ChartSettingsType::class, null, $formOptions);

        foreach (array_keys($fieldsData) as $fieldName) {
            $this->assertTrue($form->has($fieldName . 'Name'));
            $actual = $form->get($fieldName . 'Name');
            $this->assertEquals($actual->getConfig()->getOption('label'), $fieldName . 'Label');
        }
    }

    public function fieldDataProvider(): array
    {
        return [
            'name'    => [
                'fieldsData'       => [
                    'first'  => $this->getFieldData('first'),
                    'second' => $this->getFieldData('second')
                ],
                'chartName'        => 'chart_name',
                'useParentOptions' => false
            ],
            'options' => [
                'fieldsData'       => [
                    'first'  => $this->getFieldData('first'),
                    'second' => $this->getFieldData('second')
                ],
                'chartName'        => 'chart_name',
                'useParentOptions' => true
            ]
        ];
    }

    private function getFieldData(string $fieldName): array
    {
        return [
            'name'    => $fieldName . 'Name',
            'label'   => $fieldName . 'Label',
            'type'    => TextType::class,
            'options' => [
                'label'    => $fieldName . 'NewLabel',
                'required' => false
            ]
        ];
    }
}
