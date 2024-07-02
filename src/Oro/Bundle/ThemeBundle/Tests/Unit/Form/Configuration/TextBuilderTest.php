<?php

namespace Oro\Bundle\ThemeBundle\Tests\Unit\Form\Configuration;

use Oro\Bundle\ThemeBundle\Form\Configuration\ConfigurationChildBuilderInterface;
use Oro\Bundle\ThemeBundle\Form\Configuration\TextBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

final class TextBuilderTest extends TestCase
{
    private TextBuilder $textBuilder;
    private Packages|MockObject $packages;
    private FormBuilderInterface|MockObject $formBuilder;

    protected function setUp(): void
    {
        $this->formBuilder = $this->createMock(FormBuilderInterface::class);
        $this->packages = $this->createMock(Packages::class);
        $this->textBuilder = new TextBuilder($this->packages);
    }

    /**
     * @dataProvider getSupportsDataProvider
     */
    public function testSupports(string $type, bool $expectedResult): void
    {
        self::assertEquals(
            $expectedResult,
            $this->textBuilder->supports(['type' => $type])
        );
    }

    public function getSupportsDataProvider(): array
    {
        return [
            ['unknown_type', false],
            [TextBuilder::getType(), true],
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
                $expected['form_type'],
                $expected['options']
            );

        $this->textBuilder->buildOption($this->formBuilder, $option);
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

        $this->textBuilder->finishView(
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
                        'text' => 'text.png',
                    ]
                ],
                'data' => null,
                'assets' => [
                    'count' => 0
                ],
                'expectedAttr' => [],
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
                        'text' => 'text.png',
                    ]
                ],
                'data' => 'text',
                'assets' => [
                    'count' => 1,
                    'url' => [['text.png']],
                    'fullUrl' => ['/text.png']
                ],
                'expectedAttr' => [
                    'data-preview' => '/text.png',
                ],
                'expectedGroupAttr' => [
                    'data-page-component-view' => ConfigurationChildBuilderInterface::VIEW_MODULE_NAME,
                    'data-page-component-options' => [
                        'autoRender' => true,
                        'previewSource' => '/text.png',
                        'defaultPreview' => ''
                    ]
                ]
            ],
            'with option default data' => [
                'themeOption' => [
                    'default' => 'text',
                    'previews' => [
                        'text' => 'text.png',
                    ]
                ],
                'data' => null,
                'assets' => [
                    'count' => 1,
                    'url' => [['text.png']],
                    'fullUrl' => ['/text.png']
                ],
                'expectedAttr' => [
                    'data-preview' => '/text.png',
                ],
                'expectedGroupAttr' => [
                    'data-page-component-view' => ConfigurationChildBuilderInterface::VIEW_MODULE_NAME,
                    'data-page-component-options' => [
                        'autoRender' => true,
                        'previewSource' => '/text.png',
                        'defaultPreview' => ''
                    ]
                ]
            ],
            'with option default data and key when preview is missed' => [
                'themeOption' => [
                    'default' => 'text',
                    'previews' => [
                        ConfigurationChildBuilderInterface::DEFAULT_PREVIEW_KEY => 'default.png',
                        'text2' => 'text.png',
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
                    'data-preview' => '/default.png',
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
                    'default' => 'text',
                    'previews' => [
                        ConfigurationChildBuilderInterface::DEFAULT_PREVIEW_KEY => 'default.png',
                        'text' => 'text.png',
                    ]
                ],
                'data' => 'text',
                'assets' => [
                    'count' => 2,
                    'url' => [['default.png'], ['text.png']],
                    'fullUrl' => ['/default.png', '/text.png']
                ],
                'expectedAttr' => [
                    'data-default-preview' => '/default.png',
                    'data-preview' => '/text.png',
                ],
                'expectedGroupAttr' => [
                    'data-page-component-view' => ConfigurationChildBuilderInterface::VIEW_MODULE_NAME,
                    'data-page-component-options' => [
                        'autoRender' => true,
                        'previewSource' => '/text.png',
                        'defaultPreview' => '/default.png'
                    ]
                ]
            ],
        ];
    }

    private function optionDataProvider(): array
    {
        return [
            'with previews' => [
                [
                    'name' => 'general-text',
                    'label' => 'Text',
                    'type' => TextBuilder::getType(),
                    'default' => 'Some text',
                    'previews' => [
                        'Some text' => 'path/to/previews/text.png',
                    ],
                ],
                [
                    'name' => 'general-text',
                    'form_type' => TextType::class,
                    'options' => [
                        'required' => false,
                        'label' => 'Text',
                        'attr' => [
                            'data-role' => 'change-preview',
                            'data-preview-key' => 'general-text'
                        ]
                    ],
                ],
            ],
            'no previews' => [
                [
                    'name' => 'general-text',
                    'label' => 'Text',
                    'type' => TextBuilder::getType(),
                ],
                [
                    'name' => 'general-text',
                    'form_type' => TextType::class,
                    'options' => [
                        'required' => false,
                        'label' => 'Text',
                        'attr' => []
                    ],
                ],
            ],
        ];
    }
}
