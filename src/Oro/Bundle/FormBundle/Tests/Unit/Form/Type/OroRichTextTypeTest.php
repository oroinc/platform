<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FormBundle\Form\Type\OroRichTextType;
use Oro\Bundle\FormBundle\Provider\HtmlTagProvider;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Asset\Context\ContextInterface;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class OroRichTextTypeTest extends FormIntegrationTestCase
{
    /** @var OroRichTextType */
    protected $formType;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigManager */
    protected $configManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject|Packages */
    protected $assetsHelper;

    /** @var ContextInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $context;

    /** @var \PHPUnit\Framework\MockObject\MockObject|HtmlTagProvider */
    protected $htmlTagProvider;

    /** @var HtmlTagHelper */
    private $htmlTagHelper;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->assetsHelper = $this->createMock(Packages::class);
        $this->htmlTagProvider = $this->createMock(HtmlTagProvider::class);
        $this->context = $this->createMock(ContextInterface::class);
        $this->htmlTagHelper = $this->createMock(HtmlTagHelper::class);

        $this->formType = new OroRichTextType(
            $this->configManager,
            $this->htmlTagProvider,
            $this->context,
            $this->htmlTagHelper
        );
        $this->formType->setAssetHelper($this->assetsHelper);
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->formType, $this->configManager);
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                    OroRichTextType::class => $this->formType
                ],
                []
            ),
        ];
    }

    public function testGetParent()
    {
        $this->assertEquals(TextareaType::class, $this->formType->getParent());
    }

    public function testGetName()
    {
        $this->assertEquals('oro_rich_text', $this->formType->getName());
    }

    /**
     * @dataProvider optionsDataProvider
     * @param array $options
     * @param bool $globalEnable
     * @param array $viewData
     * @param array $elements
     * @param bool $expectedEnable
     * @param string $subfolder
     */
    public function testBuildForm(
        array $options,
        $globalEnable,
        array $viewData,
        array $elements,
        $expectedEnable = true,
        $subfolder = ''
    ) {
        $data = 'test';

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_form.wysiwyg_enabled')
            ->will($this->returnValue($globalEnable));

        $this->context->expects($this->any())
            ->method('getBasePath')
            ->willReturn($subfolder);

        $this->assetsHelper->expects($this->any())
            ->method('getUrl')
            ->will(
                $this->returnCallback(
                    function ($data) {
                        return '/prefix/' . $data;
                    }
                )
            );

        $this->htmlTagProvider->expects($this->once())
            ->method('getAllowedElements')
            ->willReturn($elements);
        $this->htmlTagProvider->expects($this->any())
            ->method('getUriSchemes')
            ->willReturn(['http' => true, 'https' => true]);

        $viewData['attr']['data-page-component-options']['enabled'] = $expectedEnable;
        $viewData['attr']['data-page-component-options']['assets_base_url'] = ltrim($subfolder . '/', '/');
        $viewData['attr']['data-page-component-options'] = json_encode(
            $viewData['attr']['data-page-component-options']
        );

        $form = $this->factory->create(OroRichTextType::class, $data, $options);
        $view = $form->createView();

        foreach ($viewData as $key => $value) {
            $this->assertArrayHasKey($key, $view->vars);
            $this->assertEquals($value['data-page-component-module'], $view->vars[$key]['data-page-component-module']);

            $expected = json_decode($value['data-page-component-options'], true);
            ksort($expected);

            $actual = json_decode($view->vars[$key]['data-page-component-options'], true);
            ksort($actual);

            $this->assertEquals($expected, $actual);
        }
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @return array
     */
    public function optionsDataProvider()
    {
        $toolbar = [
            'undo redo | formatselect | bold italic underline | forecolor backcolor | bullist numlist ' .
            '| alignleft aligncenter alignright alignjustify | link image | fullscreen'
        ];
        $elements = [
            '@[style|class]',
            'table[cellspacing|cellpadding|border|align|width]',
            'thead[align|valign]',
            'tbody[align|valign]',
            'tr[align|valign]',
            'td[align|valign|rowspan|colspan|bgcolor|nowrap|width|height]',
            'a[!href|target=_blank|title]',
            'dl',
            'dt',
            'div',
            'ul',
            'ol',
            'li',
            'em',
            'strong/b',
            'p',
            'font[color]',
            'i',
            'br',
            'span',
            'img[src|width|height|alt]',
            'h1',
            'h2',
            'h3',
            'h4',
            'h5',
            'h6',
        ];

        $defaultAttrs = [
            'data-page-component-module' => 'oroui/js/app/components/view-component',
            'data-page-component-options' => [
                'view' => 'oroform/js/app/views/wysiwig-editor/wysiwyg-editor-view',
                'content_css' => 'build/admin/tinymce/wysiwyg-editor.css',
                'plugins' => ['code', 'link', 'fullscreen', 'paste', 'lists', 'image', 'advlist'],
                'toolbar' => $toolbar,
                'valid_elements' => '',
                'menubar' => false,
                'branding' => false,
                'elementpath' => false,
                'relative_urls' => false,
                'remove_script_host' => false,
                'convert_urls' => true,
                'cache_suffix' => '',
                'document_base_url' => '/prefix//',
                'paste_data_images' => false,
            ]
        ];

        return [
            'default options options' => [
                [],
                true,
                [
                    'attr' => $defaultAttrs
                ],
                [],
            ],
            'default options global disabled' => [
                [],
                false,
                [
                    'attr' => $defaultAttrs
                ],
                [],
                false,
            ],
            'global enabled local disabled' => [
                ['wysiwyg_enabled' => false],
                true,
                [
                    'attr' => $defaultAttrs
                ],
                [],
                false,
            ],
            'wysiwyg_options' => [
                [
                    'wysiwyg_options' => [
                        'plugins' => ['link'],
                        'menubar' => true,
                        'branding' => false,
                        'elementpath' => false,
                        'toolbar_type' => OroRichTextType::TOOLBAR_SMALL
                    ]
                ],
                true,
                [
                    'attr' => [
                        'data-page-component-module' => 'oroui/js/app/components/view-component',
                        'data-page-component-options' => array_merge(
                            $defaultAttrs['data-page-component-options'],
                            [
                                'plugins' => ['link'],
                                'menubar' => true,
                                'toolbar' => [
                                    'undo redo | bold italic underline | bullist numlist | link image | fullscreen'
                                ],
                                'valid_elements' => implode(',', $elements),
                            ]
                        )
                    ]
                ],
                $elements,
            ],
            'wysiwyg_options with subfolder' => [
                [
                    'wysiwyg_options' => [
                        'plugins' => ['link'],
                        'menubar' => true,
                        'branding' => false,
                        'elementpath' => false,
                        'toolbar_type' => OroRichTextType::TOOLBAR_SMALL
                    ]
                ],
                true,
                [
                    'attr' => [
                        'data-page-component-module' => 'oroui/js/app/components/view-component',
                        'data-page-component-options' => array_merge(
                            $defaultAttrs['data-page-component-options'],
                            [
                                'plugins' => ['link'],
                                'menubar' => true,
                                'toolbar' => [
                                    'undo redo | bold italic underline | bullist numlist | link image | fullscreen'
                                ],
                                'valid_elements' => implode(',', $elements),
                                'content_css' => 'subfolder/build/admin/tinymce/wysiwyg-editor.css',
                            ]
                        )
                    ]
                ],
                $elements,
                true,
                '/subfolder'
            ],
        ];
    }
}
