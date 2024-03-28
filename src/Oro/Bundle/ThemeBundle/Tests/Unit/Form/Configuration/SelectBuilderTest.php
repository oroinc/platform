<?php

namespace Oro\Bundle\ThemeBundle\Tests\Unit\Form\Configuration;

use Oro\Bundle\ThemeBundle\Form\Configuration\SelectBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

final class SelectBuilderTest extends TestCase
{
    private SelectBuilder $selectBuilder;

    private FormBuilderInterface $formBuilder;

    protected function setUp(): void
    {
        $this->formBuilder = $this->createMock(FormBuilderInterface::class);
        $this->selectBuilder = new SelectBuilder();
    }

    public function testThatOptionSelectSupported(): void
    {
        self::assertTrue($this->selectBuilder->supports(['type'=> 'select']));
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
                $expected['type_class'],
                $expected['options']
            );

        $this->selectBuilder->buildOption(
            $this->formBuilder,
            $option
        );
    }

    private function optionDataProvider(): array
    {
        return [
            'with previews' => [
                [
                    'name' => 'general-select',
                    'label' => 'Select',
                    'default' => 'option_1',
                    'attributes' => [],
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
                    'name' => 'general-select',
                    'type_class' => ChoiceType::class,
                    'options' => [
                        'required' => false,
                        'expanded' => false,
                        'multiple' => false,
                        'placeholder' => false,
                        'label' => 'Select',
                        'empty_data' => 'option_1',
                        'attr' => [
                            'data-role' => 'change-preview',
                            'data-preview-key' => 'general-select',
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
                    'name' => 'general-select',
                    'label' => 'Select',
                    'default' => 'option_1',
                    'attributes' => [],
                    'values' => [
                        'option_1' => 'Option 1',
                        'option_2' => 'Option 2'
                    ],
                    'previews' => []
                ],
                [
                    'name' => 'general-select',
                    'type_class' => ChoiceType::class,
                    'options' => [
                        'required' => false,
                        'expanded' => false,
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
