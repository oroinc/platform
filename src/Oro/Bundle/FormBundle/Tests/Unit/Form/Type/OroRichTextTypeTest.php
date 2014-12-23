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
     * @param bool $expectedEnable
     * @param array $viewData
     */
    public function testBuildForm(array $options, $globalEnable, $expectedEnable = true, array $viewData)
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
            $this->assertEquals($value, $view->vars[$key]);
        }
    }

    /**
     * @return array
     */
    public function optionsDataProvider()
    {
        $defaultAttrs = [
            'data-page-component-module' => 'oroui/js/app/components/view-component',
            'data-page-component-options' => [
                'view' => 'oroform/js/app/views/wysiwig-editor/wysiwyg-editor-view',
                'content_css' => 'bundles/oroform/css/wysiwyg-editor.css',
                'plugins' => ['textcolor', 'code'],
                'toolbar' => ['undo redo | bold italic underline | forecolor backcolor | bullist numlist | code'],
                'menubar' => false,
                'statusbar' => false
            ]
        ];

        return [
            'default options options' => [
                [],
                true,
                true,
                [
                    'attr' => $defaultAttrs
                ]
            ],
            'default options global disabled' => [
                [],
                false,
                false,
                [
                    'attr' => $defaultAttrs
                ]
            ],
            'global enabled local disabled' => [
                ['wysiwyg_enabled' => false],
                true,
                false,
                [
                    'attr' => $defaultAttrs
                ]
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
