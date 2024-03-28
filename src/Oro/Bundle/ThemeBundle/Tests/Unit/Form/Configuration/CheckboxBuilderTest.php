<?php

namespace Oro\Bundle\ThemeBundle\Tests\Unit\Form\Configuration;

use Oro\Bundle\ThemeBundle\Form\Configuration\CheckboxBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;

final class CheckboxBuilderTest extends TestCase
{
    private CheckboxBuilder $checkboxBuilder;

    private FormBuilderInterface $formBuilder;

    protected function setUp(): void
    {
        $this->formBuilder = $this->createMock(FormBuilderInterface::class);
        $this->checkboxBuilder = new CheckboxBuilder();
    }

    public function testThatOptionSelectSupported(): void
    {
        self::assertTrue($this->checkboxBuilder->supports(['type'=> 'checkbox']));
    }

    /**
     * @dataProvider optionDataProvider
     */
    public function testThatOptionBuiltCorrectly(array $option, array $expected): void
    {
        $this->formBuilder
            ->expects(self::once())
            ->method('add')
            ->with(
                $expected['name'],
                $expected['form_type'],
                $expected['options']
            );

        $this->checkboxBuilder->buildOption($this->formBuilder, $option);
    }

    private function optionDataProvider(): array
    {
        return [
            'with previews' => [
                [
                    'name' => 'general-checkbox',
                    'label' => 'Checkbox',
                    'type' => 'checkbox',
                    'default' => 'unchecked',
                    'previews' => [
                        'checked' => 'path/to/previews/checked.png',
                        'unchecked' => 'path/to/previews/unchecked.png',
                    ]
                ],
                [
                    'name' => 'general-checkbox',
                    'form_type' => CheckboxType::class,
                    'options' => [
                        'required' => false,
                        'label' => 'Checkbox',
                        'empty_data' => 'unchecked',
                        'false_values' => ['unchecked'],
                        'attr' => [
                            'data-role' => 'change-preview',
                            'data-preview-key' => 'general-checkbox',
                            'data-preview-default' => 'unchecked',
                            'data-preview-checked' => 'path/to/previews/checked.png',
                            'data-preview-unchecked' => 'path/to/previews/unchecked.png'
                        ],
                        'data' => 'unchecked'
                    ]
                ]
            ],
            'no previews' => [
                [
                    'name' => 'general-checkbox',
                    'label' => 'Checkbox',
                    'type' => 'checkbox',
                    'default' => 'unchecked',
                    'previews' => []
                ],
                [
                    'name' => 'general-checkbox',
                    'form_type' => CheckboxType::class,
                    'options' => [
                        'required' => false,
                        'label' => 'Checkbox',
                        'empty_data' => 'unchecked',
                        'false_values' => ['unchecked'],
                        'attr' => [],
                        'data' => 'unchecked'
                    ]
                ]
            ]
        ];
    }
}
