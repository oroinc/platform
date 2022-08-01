<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\FormBundle\Form\Extension\TooltipFormExtension;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TooltipFormExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $configProvider;

    /** @var Translator|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var TooltipFormExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->configProvider = $this->createMock(ConfigProvider::class);
        $this->translator = $this->createMock(Translator::class);

        $this->extension = new TooltipFormExtension($this->configProvider, $this->translator);
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefined')
            ->with([
                'tooltip',
                'tooltip_details_enabled',
                'tooltip_details_anchor',
                'tooltip_details_link',
                'tooltip_placement',
                'tooltip_parameters'
            ]);

        $this->extension->configureOptions($resolver);
    }

    public function testBuildView()
    {
        $options = [
            'tooltip'                 => 'test',
            'tooltip_details_enabled' => true,
            'tooltip_details_anchor'  => 'test',
            'tooltip_details_link'    => 'test',
            'tooltip_placement'       => 'test'
        ];

        $form = $this->createMock(FormInterface::class);
        $parentForm = $this->createMock(FormInterface::class);
        $parentFormConfig = $this->createMock(FormConfigInterface::class);
        $form->expects($this->any())
            ->method('getParent')
            ->willReturn($parentForm);
        $form->expects($this->any())
            ->method('getName')
            ->willReturn('testField');
        $parentForm->expects($this->once())
            ->method('getConfig')
            ->willReturn($parentFormConfig);
        $parentFormConfig->expects($this->once())
            ->method('getOptions')
            ->willReturn(['data_class' => 'TestClass']);

        $this->translator->expects($this->any())
            ->method('hasTrans')
            ->willReturn(true);

        $view = new FormView();
        $this->extension->buildView($view, $form, $options);

        foreach ($options as $option => $value) {
            $this->assertArrayHasKey($option, $view->vars);
            $this->assertEquals($options[$option], $view->vars[$option]);
        }
    }

    public function testModifyFormTooltipField()
    {
        $tooltip = 'Custom Tooltip';

        $form = $this->createMock(FormInterface::class);
        $parentForm = $this->createMock(FormInterface::class);
        $parentFormConfig = $this->createMock(FormConfigInterface::class);
        $form->expects($this->any())
            ->method('getParent')
            ->willReturn($parentForm);
        $form->expects($this->any())
            ->method('getName')
            ->willReturn('testField');
        $parentForm->expects($this->once())
            ->method('getConfig')
            ->willReturn($parentFormConfig);
        $parentFormConfig->expects($this->once())
            ->method('getOptions')
            ->willReturn(['data_class' => 'TestClass']);

        $this->configProvider->expects($this->never())
            ->method('getConfig');
        $this->configProvider->expects($this->never())
            ->method('hasConfig');

        $view = new FormView();
        $this->extension->buildView($view, $form, ['tooltip' => $tooltip]);

        $this->assertEquals($tooltip, $view->vars['tooltip']);
    }

    public function testModifyEntityConfigTooltipFieldWithTooltipTranslation()
    {
        $tooltip = 'Custom Tooltip';

        $form = $this->createMock(FormInterface::class);
        $parentForm = $this->createMock(FormInterface::class);
        $parentFormConfig = $this->createMock(FormConfigInterface::class);
        $form->expects($this->any())
            ->method('getParent')
            ->willReturn($parentForm);
        $form->expects($this->any())
            ->method('getName')
            ->willReturn('testField');
        $parentForm->expects($this->once())
            ->method('getConfig')
            ->willReturn($parentFormConfig);
        $parentFormConfig->expects($this->once())
            ->method('getOptions')
            ->willReturn(['data_class' => 'TestClass']);

        $config = $this->createMock(ConfigInterface::class);
        $this->configProvider->expects($this->any())
            ->method('hasConfig')
            ->with('TestClass', 'testField')
            ->willReturn(true);
        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with('TestClass', 'testField')
            ->willReturn($config);
        $config->expects($this->once())
            ->method('get')
            ->with('description')
            ->willReturn($tooltip);

        $this->translator->expects($this->once())
            ->method('hasTrans')
            ->with($tooltip)
            ->willReturn(true);

        $view = new FormView();
        $this->extension->buildView($view, $form, []);

        $this->assertArrayHasKey('tooltip', $view->vars);
        $this->assertEquals($tooltip, $view->vars['tooltip']);
    }

    public function testModifyEntityConfigTooltipFieldWithoutTooltipTranslation()
    {
        $form = $this->createMock(FormInterface::class);
        $parentForm = $this->createMock(FormInterface::class);
        $parentFormConfig = $this->createMock(FormConfigInterface::class);
        $form->expects($this->any())
            ->method('getParent')
            ->willReturn($parentForm);
        $form->expects($this->any())
            ->method('getName')
            ->willReturn('testField');
        $parentForm->expects($this->once())
            ->method('getConfig')
            ->willReturn($parentFormConfig);
        $parentFormConfig->expects($this->once())
            ->method('getOptions')
            ->willReturn(['data_class' => 'TestClass']);

        $config = $this->createMock(ConfigInterface::class);
        $this->configProvider->expects($this->any())
            ->method('hasConfig')
            ->with('TestClass', 'testField')
            ->willReturn(true);
        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with('TestClass', 'testField')
            ->willReturn($config);
        $config->expects($this->once())
            ->method('get')
            ->with('description')
            ->willReturn('Custom Tooltip');

        $this->translator->expects($this->any())
            ->method('hasTrans')
            ->willReturn(false);

        $view = new FormView();
        $this->extension->buildView($view, $form, []);

        $this->assertArrayNotHasKey('tooltip', $view->vars);
    }

    public function testBuildViewWithoutParentForm()
    {
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->any())
            ->method('getParent')
            ->willReturn(null);

        $view = new FormView();
        $this->extension->buildView($view, $form, ['toolbar' => 'test']);

        $this->assertArrayNotHasKey('toolbar', $view->vars);
    }
}
