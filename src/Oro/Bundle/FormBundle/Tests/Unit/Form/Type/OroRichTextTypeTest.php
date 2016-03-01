<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Oro\Bundle\FormBundle\Form\Type\OroRichTextType;

class OroRichTextTypeTest extends FormIntegrationTestCase
{
    /**
     * @var OroRichTextType
     */
    protected $formType;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $configManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $assetsHelper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $htmlTagProvider;

    protected function setUp()
    {
        parent::setUp();

        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->assetsHelper = $this->getMockBuilder('Symfony\Component\Asset\Packages')
            ->disableOriginalConstructor()
            ->getMock();
        $this->htmlTagProvider = $this->getMock('Oro\Bundle\FormBundle\Provider\HtmlTagProvider');
        $this->formType = new OroRichTextType($this->configManager, $this->htmlTagProvider);
        $this->formType->setAssetHelper($this->assetsHelper);
    }

    protected function tearDown()
    {
        parent::tearDown();
        unset($this->formType, $this->configManager);
    }

    public function testGetParent()
    {
        $this->assertEquals('textarea', $this->formType->getParent());
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
     */
    public function testBuildForm(
        array $options,
        $globalEnable,
        array $viewData,
        array $elements,
        $expectedEnable = true
    ) {
        $data = 'test';

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_form.wysiwyg_enabled')
            ->will($this->returnValue($globalEnable));

        $this->assetsHelper->expects($this->once())
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

        $viewData['attr']['data-page-component-options']['content_css']
            = '/prefix/' . $viewData['attr']['data-page-component-options']['content_css'];
        $viewData['attr']['data-page-component-options']['skin_url']
            = '/prefix/' . $viewData['attr']['data-page-component-options']['skin_url'];
        $viewData['attr']['data-page-component-options']['enabled'] = $expectedEnable;
        $viewData['attr']['data-page-component-options'] = json_encode(
            $viewData['attr']['data-page-component-options']
        );

        $form = $this->factory->create($this->formType, $data, $options);
        $view = $form->createView();

        foreach ($viewData as $key => $value) {
            $this->assertArrayHasKey($key, $view->vars);
            $this->assertEquals($value['data-page-component-module'], $view->vars[$key]['data-page-component-module']);
            
            $expected = json_decode($value['data-page-component-options'], true);
            $actual = json_decode($view->vars[$key]['data-page-component-options'], true);
            $this->assertEquals(ksort($expected), ksort($actual));
        }
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @return array
     */
    public function optionsDataProvider()
    {
        $toolbar = ['undo redo | bold italic underline | forecolor backcolor | bullist numlist | link | code'];
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
                'content_css' => 'bundles/oroform/css/wysiwyg-editor.css',
                'skin_url' => 'bundles/oroform/css/tinymce',
                'plugins' => ['textcolor', 'code', 'link'],
                'toolbar' => $toolbar,
                'valid_elements' => implode(',', $elements),
                'menubar' => false,
                'statusbar' => false,
                'relative_urls' => false,
                'remove_script_host' => false,
                'convert_urls' => true,
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
                        'plugins' => ['textcolor'],
                        'menubar' => true,
                        'statusbar' => false,
                        'toolbar_type' => OroRichTextType::TOOLBAR_SMALL
                    ]
                ],
                true,
                [
                    'attr' => [
                        'data-page-component-module' => 'oroui/js/app/components/view-component',
                        'data-page-component-options' => array_merge(
                            [
                                'view' => 'oroform/js/app/views/wysiwig-editor/wysiwyg-editor-view',
                                'content_css' => 'bundles/oroform/css/wysiwyg-editor.css',
                                'skin_url' => 'bundles/oroform/css/tinymce',
                                'plugins' => ['textcolor', 'code', 'link'],
                                'toolbar' => $toolbar,
                                'menubar' => false,
                                'statusbar' => false,
                                'relative_urls' => false,
                                'remove_script_host' => false,
                                'convert_urls' => true,
                            ],
                            [
                                'plugins' => ['textcolor'],
                                'menubar' => true,
                                'statusbar' => false,
                                'toolbar' => ['undo redo | bold italic underline | bullist numlist link']
                            ]
                        )
                    ]
                ],
                $elements,
            ],
        ];
    }
}
