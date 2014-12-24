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

    protected function setUp()
    {
        parent::setUp();

        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->assetsHelper = $this->getMock('Symfony\Component\Templating\Asset\PackageInterface');
        $this->formType = new OroRichTextType($this->configManager);
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
     * @param bool $expectedEnable
     */
    public function testBuildForm(array $options, $globalEnable, array $viewData, $expectedEnable = true)
    {
        $data = 'test';

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_form.wysiwyg_enabled')
            ->will($this->returnValue($globalEnable));

        $assetCss = 'some.css';
        $this->assetsHelper->expects($this->once())
            ->method('getUrl')
            ->with($viewData['attr']['data-page-component-options']['content_css'])
            ->will($this->returnValue($assetCss));

        $viewData['attr']['data-page-component-options']['content_css'] = $assetCss;
        $viewData['attr']['data-page-component-options']['enabled'] = $expectedEnable;
        $viewData['attr']['data-page-component-options'] = json_encode(
            $viewData['attr']['data-page-component-options']
        );

        $form = $this->factory->create($this->formType, $data, $options);
        $view = $form->createView();

        foreach ($viewData as $key => $value) {
            $this->assertArrayHasKey($key, $view->vars);
            $this->assertEquals($value['data-page-component-module'], $view->vars[$key]['data-page-component-module']);
            $this->assertEquals(
                json_decode($value['data-page-component-options'], true),
                json_decode($view->vars[$key]['data-page-component-options'], true)
            );
        }
    }

    /**
     * @return array
     */
    public function optionsDataProvider()
    {
        $toolbar = ['undo redo | bold italic underline | forecolor backcolor | bullist numlist | code | link'];
        $elements = 'a[href|target=_blank],ul,ol,li,em[style],strong,b,p,font[color],i,br[data-mce-bogus]';

        $defaultAttrs = [
            'data-page-component-module' => 'oroui/js/app/components/view-component',
            'data-page-component-options' => [
                'view' => 'oroform/js/app/views/wysiwig-editor/wysiwyg-editor-view',
                'content_css' => 'bundles/oroform/css/wysiwyg-editor.css',
                'plugins' => ['textcolor', 'code', 'link'],
                'toolbar' => $toolbar,
                'valid_elements' => $elements,
                'menubar' => false,
                'statusbar' => false
            ]
        ];

        return [
            'default options options' => [
                [],
                true,
                [
                    'attr' => $defaultAttrs
                ]
            ],
            'default options global disabled' => [
                [],
                false,
                [
                    'attr' => $defaultAttrs
                ],
                false,
            ],
            'global enabled local disabled' => [
                ['wysiwyg_enabled' => false],
                true,
                [
                    'attr' => $defaultAttrs
                ],
                false,
            ],
            'wysiwyg_options' => [
                [
                    'wysiwyg_options' => [
                        'plugins' => ['textcolor'],
                        'toolbar' => ['undo redo | bold italic underline | forecolor backcolor'],
                        'menubar' => true,
                        'statusbar' => false
                    ]
                ],
                true,
                [
                    'attr' => [
                        'data-page-component-module' => 'oroui/js/app/components/view-component',
                        'data-page-component-options' => [
                            'view' => 'oroform/js/app/views/wysiwig-editor/wysiwyg-editor-view',
                            'content_css' => 'bundles/oroform/css/wysiwyg-editor.css',
                            'plugins' => ['textcolor'],
                            'toolbar' => ['undo redo | bold italic underline | forecolor backcolor'],
                            'menubar' => true,
                            'statusbar' => false
                        ]
                    ]
                ]
            ],
        ];
    }
}
