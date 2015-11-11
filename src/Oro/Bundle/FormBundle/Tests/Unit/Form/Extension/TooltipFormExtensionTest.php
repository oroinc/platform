<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\FormBundle\Form\Extension\TooltipFormExtension;
use Symfony\Component\Form\FormView;

class TooltipFormExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $fieldProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $translator;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $form;

    protected function setUp()
    {
        $this->fieldProvider = $this
            ->getMockBuilder('Oro\Bundle\EntityBundle\Provider\EntityFieldProvider')
            ->disableOriginalConstructor()
            ->getMock();
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
            ->getMockBuilder('Symfony\Component\Translation\TranslatorInterface')
            ->setMethods(['trans', 'hasTrans', 'transChoice', 'setLocale', 'getLocale'])
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testSetDefaultOptions()
    {
        $resolver = $this->getMockBuilder('Symfony\Component\OptionsResolver\OptionsResolverInterface')
            ->getMock();
        $resolver->expects($this->once())
            ->method('setOptional')
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

        $extension = new TooltipFormExtension($this->fieldProvider, $this->configProvider, $this->translator);
        $extension->setDefaultOptions($resolver);
        $this->assertEquals('form', $extension->getExtendedType());
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
        $this->translator->expects($this->once())
            ->method('trans')
            ->will($this->returnValue('test'));
        $this->translator->expects($this->any())
            ->method('hasTrans')
            ->will($this->returnValue(true));
        $extension = new TooltipFormExtension($this->fieldProvider, $this->configProvider, $this->translator);
        $extension->buildView($view, $form, $options);

        foreach ($options as $option => $value) {
            $this->assertArrayHasKey($option, $view->vars);
            $this->assertEquals($options[$option], $view->vars[$option]);
        }
    }

    /**
     * @param int $iteration
     * @param array $options
     * @param string $domain
     * @param string $translatedTooltip
     * @dataProvider modifyFormTooltipFieldProvider
     */
    public function testModifyFormTooltipField($iteration, $options, $domain, $translatedTooltip)
    {
        $translator = $this
            ->getMockBuilder('Symfony\Component\Translation\TranslatorInterface')
            ->setMethods(['trans', 'hasTrans', 'transChoice', 'setLocale', 'getLocale'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockMethods($this->form, ['getParent', 'getConfig', 'getOptions'], ['data_class' => 'TestClass']);
        $view = new FormView();
        if ($iteration !== null) {
            $translator
                ->expects($this->at($iteration))
                ->method('hasTrans')
                ->with($this->stringContains($options['tooltip']), $this->stringContains($domain))
                ->will($this->returnValue(true));
        }
        $translator
            ->expects($this->any())
            ->method('trans')
            ->with($options['tooltip'], [], $domain)
            ->will($this->returnValue($translatedTooltip));
        $this->configProvider->expects($this->any())
            ->method('hasConfig')
            ->will($this->returnValue(true));
        $this->form->expects($this->any())
            ->method('getOption')
            ->will($this->returnValue(false));

        $extension = new TooltipFormExtension($this->fieldProvider, $this->configProvider, $translator);
        $extension->buildView($view, $this->form, $options);

        $this->assertEquals($view->vars['tooltip'], $translatedTooltip);
    }

    /**
     * @param array $options
     * @param string $tooltip
     * @param string $translatedTooltip
     * @param bool $isEmptyViewTooltip
     * @dataProvider testModifyEntityConfigTooltipFieldProvider
     */
    public function testModifyEntityConfigTooltipField($options, $tooltip, $translatedTooltip, $isEmptyViewTooltip)
    {
        $translator = $this
            ->getMockBuilder('Symfony\Component\Translation\TranslatorInterface')
            ->setMethods(['trans', 'transChoice', 'setLocale', 'getLocale'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockMethods($this->form, ['getParent', 'getConfig', 'getOptions'], ['data_class' => 'TestClass']);
        $this->mockMethods($this->configProvider, ['getConfig', 'get'], $tooltip);
        $view = new FormView();
        $translator
            ->expects($this->any())
            ->method('trans')
            ->with($tooltip)
            ->will($this->returnValue($translatedTooltip));
        $this->configProvider->expects($this->any())
            ->method('hasConfig')
            ->will($this->returnValue(true));
        $this->form->expects($this->any())
            ->method('getOption')
            ->will($this->returnValue(false));

        $extension = new TooltipFormExtension($this->fieldProvider, $this->configProvider, $translator);
        $extension->buildView($view, $this->form, $options);

        if ($isEmptyViewTooltip) {
            $this->assertArrayNotHasKey('tooltip', $view->vars);
        } else {
            $this->assertArrayHasKey('tooltip', $view->vars);
            $this->assertEquals($view->vars['tooltip'], $translatedTooltip);
        }
    }

    public function testParentForm()
    {
        $view = new FormView();
        $this->form->expects($this->any())
            ->method('getParent')
            ->will($this->returnValue(false));
        $translator = $this
            ->getMockBuilder('Symfony\Component\Translation\TranslatorInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $extension = new TooltipFormExtension($this->fieldProvider, $this->configProvider, $translator);
        $extension->buildView($view, $this->form, ['toolbar' => 'test']);
        $this->assertArrayNotHasKey('toolbar', $view->vars);
    }

    /**
     * @return array
     */
    public function modifyFormTooltipFieldProvider()
    {
        return [
            'Test with form tooltip via messages domain' => [
                'iteration' => 0,
                'options' => ['tooltip' => 'Custom Messages Domain Tooltip'],
                'domain' => 'messages',
                'translatedTooltip' => 'Translated Custom Messages Domain Tooltip'
            ],
            'Test with form tooltip via tooltips domain' => [
                'iteration' => 1,
                'options' => ['tooltip' => 'Custom Tooltips Domain Tooltip'],
                'domain' => 'tooltips',
                'translatedTooltip' => 'Translated Custom Tooltips Domain Tooltip'
            ],
            'Test with form tooltip via unknown domain' => [
                'iteration' => null,
                'options' => ['tooltip' => 'The Same'],
                'domain' => 'unknown',
                'translatedTooltip' => 'The Same'
            ]
        ];
    }

    /**
     * @return array
     */
    public function testModifyEntityConfigTooltipFieldProvider()
    {
        return [
            'Test translated entity config field with difference value' => [
                'options' => [],
                'tooltip' => 'Custom Tooltip',
                'translatedTooltip' => 'Translated Custom Tooltip',
                'isEmptyViewTooltip' => false
            ],
            'Test translated entity config field with equal value' => [
                'options' => [],
                'tooltip' => 'Custom Tooltip',
                'translatedTooltip' => 'Custom Tooltip',
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
