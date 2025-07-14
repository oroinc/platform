<?php

namespace Oro\Bundle\ThemeBundle\Tests\Unit\Form\Configuration;

use Oro\Bundle\ThemeBundle\Form\Configuration\CheckboxBuilder;
use Oro\Bundle\ThemeBundle\Form\Configuration\ConfigurationChildBuilderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

final class CheckboxBuilderTest extends TestCase
{
    private Packages&MockObject $packages;
    private FormBuilderInterface&MockObject $formBuilder;
    private CheckboxBuilder $checkboxBuilder;

    #[\Override]
    protected function setUp(): void
    {
        $this->formBuilder = $this->createMock(FormBuilderInterface::class);
        $this->packages = $this->createMock(Packages::class);
        $this->checkboxBuilder = new CheckboxBuilder($this->packages);
    }

    /**
     * @dataProvider getSupportsDataProvider
     */
    public function testSupports(string $type, bool $expectedResult): void
    {
        self::assertEquals(
            $expectedResult,
            $this->checkboxBuilder->supports(['type' => $type])
        );
    }

    public function getSupportsDataProvider(): array
    {
        return [
            ['unknown_type', false],
            [CheckboxBuilder::getType(), true],
        ];
    }

    /**
     * @dataProvider optionDataProvider
     */
    public function testThatOptionBuiltCorrectly(array $option, array $expected): void
    {
        $this->formBuilder->expects(self::once())
            ->method('add')
            ->with(
                $expected['name'],
                $expected['form_type'],
                $expected['options']
            );

        $this->checkboxBuilder->buildOption($this->formBuilder, $option);
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
            $this->packages->expects(self::exactly($assets['count']))
                ->method('getUrl')
                ->withConsecutive(...$assets['url'])
                ->willReturnOnConsecutiveCalls(...$assets['fullUrl']);
        } else {
            $this->packages->expects(self::never())
                ->method('getUrl');
        }

        $this->checkboxBuilder->finishView(
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
                        'checked' => 'checked.png',
                        'unchecked' => 'unchecked.png',
                    ]
                ],
                'data' => null,
                'assets' => [
                    'count' => 2,
                    'url' => [['checked.png'], ['unchecked.png']],
                    'fullUrl' => ['/checked.png', '/unchecked.png']
                ],

                'expectedAttr' => [
                    'data-preview-checked' => '/checked.png',
                    'data-preview-unchecked' => '/unchecked.png'
                ],
                'expectedGroupAttr' => [
                    'data-page-component-view' => ConfigurationChildBuilderInterface::VIEW_MODULE_NAME,
                    'data-page-component-options' => [
                        'autoRender' => true,
                        'previewSource' => '',
                        'defaultPreview' => ''
                    ]
                ]
            ],
            'with form data' => [
                'themeOption' => [
                    'previews' => [
                        'checked' => 'checked.png',
                        'unchecked' => 'unchecked.png',
                    ]
                ],
                'data' => 'checked',
                'assets' => [
                    'count' => 3,
                    'url' => [['checked.png'], ['checked.png'], ['unchecked.png']],
                    'fullUrl' => ['/checked.png', '/checked.png', '/unchecked.png']
                ],
                'expectedAttr' => [
                    'data-preview' => '/checked.png',
                    'data-preview-checked' => '/checked.png',
                    'data-preview-unchecked' => '/unchecked.png'
                ],
                'expectedGroupAttr' => [
                    'data-page-component-view' => ConfigurationChildBuilderInterface::VIEW_MODULE_NAME,
                    'data-page-component-options' => [
                        'autoRender' => true,
                        'previewSource' => '/checked.png',
                        'defaultPreview' => ''
                    ]
                ]
            ],
            'with option default data' => [
                'themeOption' => [
                    'default' => 'unchecked',
                    'previews' => [
                        'checked' => 'checked.png',
                        'unchecked' => 'unchecked.png',
                    ]
                ],
                'data' => null,
                'assets' => [
                    'count' => 3,
                    'url' => [['unchecked.png'], ['checked.png'], ['unchecked.png']],
                    'fullUrl' => ['/unchecked.png', '/checked.png', '/unchecked.png']
                ],
                'expectedAttr' => [
                    'data-preview' => '/unchecked.png',
                    'data-preview-checked' => '/checked.png',
                    'data-preview-unchecked' => '/unchecked.png'
                ],
                'expectedGroupAttr' => [
                    'data-page-component-view' => ConfigurationChildBuilderInterface::VIEW_MODULE_NAME,
                    'data-page-component-options' => [
                        'autoRender' => true,
                        'previewSource' => '/unchecked.png',
                        'defaultPreview' => ''
                    ]
                ]
            ],
            'with option default data and key when preview is missed' => [
                'themeOption' => [
                    'default' => 'checked',
                    'previews' => [
                        ConfigurationChildBuilderInterface::DEFAULT_PREVIEW_KEY => 'default.png',
                        'unchecked' => 'unchecked.png',
                    ]
                ],
                'data' => null,
                'assets' => [
                    'count' => 3,
                    'url' => [['default.png'], ['default.png'], ['unchecked.png']],
                    'fullUrl' => ['/default.png', '/default.png', '/unchecked.png']
                ],
                'expectedAttr' => [
                    'data-default-preview' => '/default.png',
                    'data-preview' => '/default.png',
                    'data-preview-unchecked' => '/unchecked.png'
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
                    'default' => 'checked',
                    'previews' => [
                        ConfigurationChildBuilderInterface::DEFAULT_PREVIEW_KEY => 'default.png',
                        'checked' => 'checked.png',
                        'unchecked' => 'unchecked.png',
                    ]
                ],
                'data' => 'unchecked',
                'assets' => [
                    'count' => 4,
                    'url' => [['default.png'], ['unchecked.png'], ['checked.png'], ['unchecked.png']],
                    'fullUrl' => ['/default.png', '/unchecked.png', '/checked.png', '/unchecked.png']
                ],
                'expectedAttr' => [
                    'data-default-preview' => '/default.png',
                    'data-preview' => '/unchecked.png',
                    'data-preview-checked' => '/checked.png',
                    'data-preview-unchecked' => '/unchecked.png'
                ],
                'expectedGroupAttr' => [
                    'data-page-component-view' => ConfigurationChildBuilderInterface::VIEW_MODULE_NAME,
                    'data-page-component-options' => [
                        'autoRender' => true,
                        'previewSource' => '/unchecked.png',
                        'defaultPreview' => '/default.png'
                    ]
                ]
            ],
            'with form bool data' => [
                'themeOption' => [
                    'previews' => [
                        'checked' => 'checked.png',
                        'unchecked' => 'unchecked.png',
                    ]
                ],
                'data' => false,
                'assets' => [
                    'count' => 3,
                    'url' => [['unchecked.png'], ['checked.png'], ['unchecked.png']],
                    'fullUrl' => ['/unchecked.png', '/checked.png', '/unchecked.png']
                ],
                'expectedAttr' => [
                    'data-preview' => '/unchecked.png',
                    'data-preview-checked' => '/checked.png',
                    'data-preview-unchecked' => '/unchecked.png'
                ],
                'expectedGroupAttr' => [
                    'data-page-component-view' => ConfigurationChildBuilderInterface::VIEW_MODULE_NAME,
                    'data-page-component-options' => [
                        'autoRender' => true,
                        'previewSource' => '/unchecked.png',
                        'defaultPreview' => ''
                    ]
                ]
            ]
        ];
    }

    private function optionDataProvider(): array
    {
        return [
            'with previews' => [
                [
                    'name' => 'general-checkbox',
                    'label' => 'Checkbox',
                    'type' => CheckboxBuilder::getType(),
                    'default' => 'unchecked',
                    'previews' => [
                        'checked' => 'path/to/previews/checked.png',
                        'unchecked' => 'path/to/previews/unchecked.png',
                    ],
                ],
                [
                    'name' => 'general-checkbox',
                    'form_type' => CheckboxType::class,
                    'options' => [
                        'required' => false,
                        'label' => 'Checkbox',
                        'false_values' => ['unchecked', null],
                        'attr' => [
                            'data-role' => 'change-preview',
                            'data-preview-key' => 'general-checkbox',
                        ],
                    ],
                ],
            ],
            'no previews' => [
                [
                    'name' => 'general-checkbox',
                    'label' => 'Checkbox',
                    'type' => CheckboxBuilder::getType(),
                    'default' => 'unchecked',
                    'previews' => [],
                ],
                [
                    'name' => 'general-checkbox',
                    'form_type' => CheckboxType::class,
                    'options' => [
                        'required' => false,
                        'label' => 'Checkbox',
                        'false_values' => ['unchecked', null],
                        'attr' => [],
                    ],
                ],
            ],
        ];
    }
}
