<?php

namespace Oro\Bundle\ThemeBundle\Tests\Unit\Form\Configuration;

use Oro\Bundle\ThemeBundle\Form\Configuration\ConfigurationChildBuilderInterface;
use Oro\Bundle\ThemeBundle\Form\Configuration\SelectBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

final class SelectBuilderTest extends TestCase
{
    private SelectBuilder $selectBuilder;
    private Packages|MockObject $packages;
    private FormBuilderInterface|MockObject $formBuilder;

    #[\Override]
    protected function setUp(): void
    {
        $this->formBuilder = $this->createMock(FormBuilderInterface::class);
        $this->packages = $this->createMock(Packages::class);
        $this->selectBuilder = new SelectBuilder($this->packages);
    }

    /**
     * @dataProvider getSupportsDataProvider
     */
    public function testSupports(string $type, bool $expectedResult): void
    {
        self::assertEquals(
            $expectedResult,
            $this->selectBuilder->supports(['type' => $type])
        );
    }

    public function getSupportsDataProvider(): array
    {
        return [
            ['unknown_type', false],
            [SelectBuilder::getType(), true],
        ];
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

    /**
     * @dataProvider finishViewDataProvider
     */
    public function testThatFinishViewCorrectly(
        array $themeOption,
        mixed $data,
        array $assets,
        array $expectedAttr,
        array $expectedGroupAttr
    ): void {
        $formView = new FormView();
        $form = $this->createMock(FormInterface::class);
        $form->expects(self::any())
            ->method('getData')
            ->willReturn($data);

        if ($assets['count'] > 0) {
            $this->packages
                ->expects(self::exactly($assets['count']))
                ->method('getUrl')
                ->withConsecutive(...$assets['url'])
                ->willReturnOnConsecutiveCalls(...$assets['fullUrl']);
        } else {
            $this->packages
                ->expects(self::never())
                ->method('getUrl');
        }

        $this->selectBuilder->finishView(
            $formView,
            $form,
            [],
            $themeOption
        );

        self::assertEquals($expectedAttr, $formView->vars['attr']);
        self::assertEquals($expectedGroupAttr, $formView->vars['group_attr'] ?? []);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private function finishViewDataProvider(): array
    {
        return [
            'without previews key' => [
                'themeOption' => [],
                'data' => null,
                'assets' => [
                    'count' => 0,
                    'url' => [],
                    'fullUrl' => []
                ],
                'expectedAttr' => [],
                'expectedGroupAttr' => [],
            ],
            'with empty previews key' => [
                'themeOption' => ['previews' => []],
                'data' => null,
                'assets' => [
                    'count' => 0,
                    'url' => [],
                    'fullUrl' => []
                ],
                'expectedAttr' => [],
                'expectedGroupAttr' => [],
            ],
            'with default previews key' => [
                'themeOption' => [
                    'previews' => [
                        ConfigurationChildBuilderInterface::DEFAULT_PREVIEW_KEY => 'default.png'
                    ]
                ],
                'data' => null,
                'assets' => [
                    'count' => 2,
                    'url' => [['default.png'], ['default.png']],
                    'fullUrl' => ['/default.png', '/default.png']
                ],
                'expectedAttr' => [
                    'data-default-preview' => '/default.png',
                    'data-preview' => '/default.png'
                ],
                'expectedGroupAttr' => [
                    'data-page-component-view' => ConfigurationChildBuilderInterface::VIEW_MODULE_NAME,
                    'data-page-component-options' => [
                        'autoRender' => true,
                        'previewSource' => '/default.png',
                        'defaultPreview' => '/default.png'
                    ]
                ]
            ],
            'with previews keys' => [
                'themeOption' => [
                    'previews' => [
                        'option_1' => 'option.png',
                        'option_2' => 'option2.png',
                    ]
                ],
                'data' => null,
                'assets' => [
                    'count' => 0,
                    'url' => [],
                    'fullUrl' => []
                ],
                'expectedAttr' => [],
                'expectedGroupAttr' => [
                    'data-page-component-view' => ConfigurationChildBuilderInterface::VIEW_MODULE_NAME,
                    'data-page-component-options' => [
                        'autoRender' => true,
                        'previewSource' => '',
                        'defaultPreview' => ''
                    ]
                ],
            ],
            'with form data' => [
                'themeOption' => [
                    'previews' => [
                        'option_1' => 'option.png',
                        'option_2' => 'option2.png',
                    ]
                ],
                'data' => 'option_2',
                'assets' => [
                    'count' => 1,
                    'url' => [['option2.png']],
                    'fullUrl' => ['/option2.png']
                ],
                'expectedAttr' => [
                    'data-preview' => '/option2.png'
                ],
                'expectedGroupAttr' => [
                    'data-page-component-view' => ConfigurationChildBuilderInterface::VIEW_MODULE_NAME,
                    'data-page-component-options' => [
                        'autoRender' => true,
                        'previewSource' => '/option2.png',
                        'defaultPreview' => ''
                    ]
                ],
            ],
            'with option default data' => [
                'themeOption' => [
                    'default' => 'option_1',
                    'previews' => [
                        'option_1' => 'option.png',
                        'option_2' => 'option2.png',
                    ]
                ],
                'data' => null,
                'assets' => [
                    'count' => 1,
                    'url' => [['option.png']],
                    'fullUrl' => ['/option.png']
                ],
                'expectedAttr' => [
                    'data-preview' => '/option.png'
                ],
                'expectedGroupAttr' => [
                    'data-page-component-view' => ConfigurationChildBuilderInterface::VIEW_MODULE_NAME,
                    'data-page-component-options' => [
                        'autoRender' => true,
                        'previewSource' => '/option.png',
                        'defaultPreview' => ''
                    ]
                ]
            ],
            'with option default data and key when preview is missed' => [
                'themeOption' => [
                    'default' => 'option_1',
                    'previews' => [
                        ConfigurationChildBuilderInterface::DEFAULT_PREVIEW_KEY => 'default.png',
                        'option_2' => 'option2.png',
                    ]
                ],
                'data' => null,
                'assets' => [
                    'count' => 2,
                    'url' => [['default.png'], ['default.png']],
                    'fullUrl' => ['/default.png', '/default.png']
                ],
                'expectedAttr' => [
                    'data-default-preview' => '/default.png',
                    'data-preview' => '/default.png'
                ],
                'expectedGroupAttr' => [
                    'data-page-component-view' => ConfigurationChildBuilderInterface::VIEW_MODULE_NAME,
                    'data-page-component-options' => [
                        'autoRender' => true,
                        'previewSource' => '/default.png',
                        'defaultPreview' => '/default.png'
                    ]
                ]
            ],
            'with form data, option default data and key' => [
                'themeOption' => [
                    'default' => 'option_1',
                    'previews' => [
                        ConfigurationChildBuilderInterface::DEFAULT_PREVIEW_KEY => 'default.png',
                        'option_1' => 'option.png',
                        'option_2' => 'option2.png',
                    ]
                ],
                'data' => 'option_2',
                'assets' => [
                    'count' => 2,
                    'url' => [['default.png'], ['option2.png']],
                    'fullUrl' => ['/default.png', '/option2.png']
                ],
                'expectedAttr' => [
                    'data-default-preview' => '/default.png',
                    'data-preview' => '/option2.png'
                ],
                'expectedGroupAttr' => [
                    'data-page-component-view' => ConfigurationChildBuilderInterface::VIEW_MODULE_NAME,
                    'data-page-component-options' => [
                        'autoRender' => true,
                        'previewSource' => '/option2.png',
                        'defaultPreview' => '/default.png'
                    ]
                ]
            ],
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private function optionDataProvider(): array
    {
        return [
            'with previews' => [
                [
                    'name' => 'general-select',
                    'label' => 'Select',
                    'type' => SelectBuilder::getType(),
                    'default' => 'option_1',
                    'attributes' => [],
                    'values' => [
                        'option_1' => 'Option 1',
                        'option_2' => 'Option 2'
                    ],
                    'previews' => [
                        'option_1' => 'path/to/previews/option_1.png',
                        'option_2' => 'path/to/previews/option_2.png',
                    ],
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
                        'attr' => [
                            'data-role' => 'change-preview',
                            'data-preview-key' => 'general-select',
                        ],
                        'choices' => [
                            'Option 1' => 'option_1',
                            'Option 2' => 'option_2'
                        ],
                        'choice_attr' => function () {
                        }
                    ],
                ],
            ],
            'no previews' => [
                [
                    'name' => 'general-select',
                    'label' => 'Select',
                    'type' => SelectBuilder::getType(),
                    'default' => 'option_1',
                    'attributes' => [],
                    'values' => [
                        'option_1' => 'Option 1',
                        'option_2' => 'Option 2'
                    ],
                    'previews' => [],
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
                        'attr' => [],
                        'choices' => [
                            'Option 1' => 'option_1',
                            'Option 2' => 'option_2'
                        ],
                        'choice_attr' => function () {
                        }
                    ],
                ],
            ],
            'multiple select' => [
                [
                    'name' => 'general-select',
                    'label' => 'Select',
                    'type' => SelectBuilder::getType(),
                    'default' => 'option_1',
                    'attributes' => [],
                    'values' => [
                        'option_1' => 'Option 1',
                        'option_2' => 'Option 2'
                    ],
                    'previews' => [],
                    'options' => [
                        'multiple' => true
                    ]
                ],
                [
                    'name' => 'general-select',
                    'type_class' => ChoiceType::class,
                    'options' => [
                        'required' => false,
                        'expanded' => false,
                        'multiple' => true,
                        'placeholder' => false,
                        'label' => 'Select',
                        'attr' => [],
                        'choices' => [
                            'Option 1' => 'option_1',
                            'Option 2' => 'option_2'
                        ],
                        'choice_attr' => function () {
                        }
                    ],
                ],
            ],
        ];
    }
}
