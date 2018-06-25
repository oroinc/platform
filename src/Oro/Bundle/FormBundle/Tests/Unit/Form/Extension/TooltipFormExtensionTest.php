<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\FormBundle\Form\Extension\TooltipFormExtension;
use Symfony\Component\Form\FormView;

class TooltipFormExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $configProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $translator;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $form;

    protected function setUp()
    {
        $this->configProvider = $this
            ->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->setMethods(['hasConfig', 'getConfig', 'get'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->form = $this
            ->getMockBuilder('Symfony\Component\Form\Form')
            ->setMethods(['getParent', 'has', 'get', 'getConfig', 'getOption', 'getName', 'getOptions', 'getType'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->translator = $this
            ->getMockBuilder('Oro\Bundle\TranslationBundle\Translation\Translator')
            ->setMethods(['trans', 'hasTrans', 'transChoice', 'setLocale', 'getLocale'])
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testConfigureOptions()
    {
        $resolver = $this->getMockBuilder('Symfony\Component\OptionsResolver\OptionsResolver')
            ->getMock();
        $resolver->expects($this->once())
            ->method('setDefined')
            ->with(
                array(
                    'tooltip',
                    'tooltip_details_enabled',
                    'tooltip_details_anchor',
                    'tooltip_details_link',
                    'tooltip_placement',
                    'tooltip_parameters'
                )
            );

        $extension = new TooltipFormExtension($this->configProvider, $this->translator);
        $extension->configureOptions($resolver);
    }

    public function testBuildView()
    {
        $options = array(
            'tooltip' => 'test',
            'tooltip_details_enabled' => true,
            'tooltip_details_anchor' => 'test',
            'tooltip_details_link' => 'test',
            'tooltip_placement' => 'test'
        );
        $view = new FormView();
        $form = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->setMethods(['getParent', 'getConfig', 'getOption', 'getOptions'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockMethods($form, ['getParent', 'getConfig', 'getOptions', 'getOption'], ['data_class' => 'abc']);
        $this->translator->expects($this->any())
            ->method('hasTrans')
            ->will($this->returnValue(true));
        $extension = new TooltipFormExtension($this->configProvider, $this->translator);
        $extension->buildView($view, $form, $options);

        foreach ($options as $option => $value) {
            $this->assertArrayHasKey($option, $view->vars);
            $this->assertEquals($options[$option], $view->vars[$option]);
        }
    }

    /**
     * @param array $options
     * @param string $expectedTooltip
     * @dataProvider modifyFormTooltipFieldProvider
     */
    public function testModifyFormTooltipField($options, $expectedTooltip)
    {
        $translator = $this
            ->getMockBuilder('Oro\Bundle\TranslationBundle\Translation\Translator')
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockMethods($this->form, ['getParent', 'getConfig', 'getOptions'], ['data_class' => 'TestClass']);
        $view = new FormView();
        $this->configProvider->expects($this->any())
            ->method('hasConfig')
            ->will($this->returnValue(true));
        $extension = new TooltipFormExtension($this->configProvider, $translator);
        $extension->buildView($view, $this->form, $options);

        $this->assertEquals($view->vars['tooltip'], $expectedTooltip);
    }

    /**
     * @param int $iteration
     * @param string $domain
     * @param string $tooltip
     * @param string $expectedTooltip
     * @param bool $isEmptyViewTooltip
     * @dataProvider modifyEntityConfigTooltipFieldProvider
     */
    public function testModifyEntityConfigTooltipField(
        $iteration,
        $domain,
        $tooltip,
        $expectedTooltip,
        $isEmptyViewTooltip
    ) {
        $translator = $this
            ->getMockBuilder('Oro\Bundle\TranslationBundle\Translation\Translator')
            ->setMethods(['hasTrans'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockMethods($this->form, ['getParent', 'getConfig', 'getOptions'], ['data_class' => 'TestClass']);
        $this->mockMethods($this->configProvider, ['getConfig', 'get'], $tooltip);
        $view = new FormView();
        $this->configProvider->expects($this->any())
            ->method('hasConfig')
            ->will($this->returnValue(true));
        if ($iteration !== null) {
            $translator
                ->expects($this->at($iteration))
                ->method('hasTrans')
                ->with($this->stringContains($tooltip), $this->stringContains($domain))
                ->will($this->returnValue(true));
        }
        $extension = new TooltipFormExtension($this->configProvider, $translator);
        $extension->buildView($view, $this->form, []);

        if ($isEmptyViewTooltip) {
            $this->assertArrayNotHasKey('tooltip', $view->vars);
        } else {
            $this->assertArrayHasKey('tooltip', $view->vars);
            $this->assertEquals($view->vars['tooltip'], $expectedTooltip);
        }
    }

    public function testParentForm()
    {
        $view = new FormView();
        $this->form->expects($this->any())
            ->method('getParent')
            ->will($this->returnValue(false));
        $translator = $this
            ->getMockBuilder('Oro\Bundle\TranslationBundle\Translation\Translator')
            ->disableOriginalConstructor()
            ->getMock();

        $extension = new TooltipFormExtension($this->configProvider, $translator);
        $extension->buildView($view, $this->form, ['toolbar' => 'test']);
        $this->assertArrayNotHasKey('toolbar', $view->vars);
    }

    /**
     * @return array
     */
    public function modifyFormTooltipFieldProvider()
    {
        return [
            'Test with form tooltip' => [
                'options' => ['tooltip' => 'Custom Messages Domain Tooltip'],
                'expectedTooltip' => 'Custom Messages Domain Tooltip'
            ]
        ];
    }

    /**
     * @return array
     */
    public function modifyEntityConfigTooltipFieldProvider()
    {
        return [
            'Test entity config field with messages domain' => [
                'iteration' => 0,
                'domain' => 'messages',
                'tooltip' => 'Custom Tooltip',
                'expectedTooltip' => 'Custom Tooltip',
                'isEmptyViewTooltip' => false
            ],
            'Test entity config field with tooltips domain' => [
                'iteration' => 1,
                'domain' => 'tooltips',
                'tooltip' => 'Custom Tooltip',
                'expectedTooltip' => 'Custom Tooltip',
                'isEmptyViewTooltip' => false
            ],
            'Test entity config field with unknown domain' => [
                'iteration' => null,
                'domain' => 'unknown',
                'tooltip' => null,
                'expectedTooltip' => null,
                'isEmptyViewTooltip' => true
            ]
        ];
    }


    /**
     * Mock methods recursively
     *
     * @param object $object
     * @param array $methods
     * @param mixed $lastReturnValue
     */
    protected function mockMethods($object, $methods, $lastReturnValue)
    {
        if ($methods) {
            foreach ($methods as $key => $method) {
                $mock = $object->expects($this->any())->method($method);
                if ($key >= count($methods) - 1) {
                    $mock->will($this->returnValue($lastReturnValue));
                } else {
                    $mock->will($this->returnValue($object));
                }
            }
        }
    }
}
