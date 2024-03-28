<?php

namespace Oro\Bundle\ThemeBundle\Tests\Unit\Form\Configuration;

use Oro\Bundle\ThemeBundle\Form\Configuration\RadioBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

final class RadioBuilderTest extends TestCase
{
    private RadioBuilder $radioBuilder;

    private FormBuilderInterface $formBuilder;

    protected function setUp(): void
    {
        $this->formBuilder = $this->createMock(FormBuilderInterface::class);
        $this->radioBuilder = new RadioBuilder();
    }

    public function testThatOptionSelectSupported(): void
    {
        self::assertTrue($this->radioBuilder->supports(['type'=> 'radio']));
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

        $this->radioBuilder->buildOption($this->formBuilder, $option);
    }

    private function optionDataProvider(): array
    {
        return [
            'with previews' => [
                [
                    'name' => 'general-radio',
                    'label' => 'Select',
                    'type' => 'radio',
                    'default' => 'option_1',
                    'values' => [
                        'option_1' => 'Option 1',
                        'option_2' => 'Option 2'
                    ],
                    'previews' => [
                        'option_1' => 'path/to/previews/option_1.png',
                        'option_2' => 'path/to/previews/option_2.png',
                    ]
                ],
                [
                    'name' => 'general-radio',
                    'form_type' => ChoiceType::class,
                    'options' => [
                        'required' => false,
                        'expanded' => true,
                        'multiple' => false,
                        'placeholder' => false,
                        'label' => 'Select',
                        'empty_data' => 'option_1',
                        'attr' => [
                            'data-role' => 'change-preview',
                            'data-preview-key' => 'general-radio',
                            'data-preview-default' => 'option_1',
                            'data-preview-option_1' => 'path/to/previews/option_1.png',
                            'data-preview-option_2' => 'path/to/previews/option_2.png'
                        ],
                        'choices' => [
                            'Option 1' => 'option_1',
                            'Option 2' => 'option_2'
                        ],
                        'data' => 'option_1'
                    ]
                ]
            ],
            'no previews' => [
                [
                    'name' => 'general-radio',
                    'label' => 'Select',
                    'type' => 'radio',
                    'default' => 'option_1',
                    'values' => [
                        'option_1' => 'Option 1',
                        'option_2' => 'Option 2'
                    ]
                ],
                [
                    'name' => 'general-radio',
                    'form_type' => ChoiceType::class,
                    'options' => [
                        'required' => false,
                        'expanded' => true,
                        'multiple' => false,
                        'placeholder' => false,
                        'label' => 'Select',
                        'empty_data' => 'option_1',
                        'attr' => [],
                        'choices' => [
                            'Option 1' => 'option_1',
                            'Option 2' => 'option_2'
                        ],
                        'data' => 'option_1'
                    ]
                ]
            ]
        ];
    }
}
