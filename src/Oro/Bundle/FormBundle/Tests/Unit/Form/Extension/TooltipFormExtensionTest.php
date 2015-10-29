<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Extension;

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
            ->setMethods(['has', 'get', 'getConfig', 'getOption', 'getName', 'getOptions', 'getType'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->translator = $this
            ->getMockBuilder('Symfony\Component\Translation\TranslatorInterface')
            ->setMethods(['trans', 'transChoice', 'setLocale', 'getLocale'])
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
            ->disableOriginalConstructor()
            ->getMock();
        $extension = new TooltipFormExtension($this->fieldProvider, $this->configProvider, $this->translator);
        $extension->buildView($view, $form, $options);

        foreach ($options as $option => $value) {
            $this->assertArrayHasKey($option, $view->vars);
            $this->assertEquals($options[$option], $view->vars[$option]);
        }
    }

    /**
     * @param string $formTooltipValue
     * @param bool $valueIsOverriden
     *
     * @dataProvider modifyTooltipFieldProvider
     */
    public function testModifyTooltipField($formTooltipValue, $valueIsOverriden)
    {
        $fields = [['name' => 'test_field']];
        $options['data_class'] = 'Oro\Bundle\UserBundle\Entity\User';

        $this->fieldProvider
            ->expects($this->once())
            ->method('getFields')
            ->with($options['data_class'])
            ->will($this->returnValue($fields));
        $this->configProvider
            ->expects($this->once())
            ->method('hasConfig')
            ->will($this->returnValue(true));
        $this->form
            ->expects($this->once())
            ->method('has')
            ->with('test_field')
            ->will($this->returnValue(true));
        $this->form
            ->expects($this->exactly((int) $valueIsOverriden))
            ->method('getName');
        $this->translator
            ->expects($this->exactly((int) $valueIsOverriden))
            ->method('trans')
            ->will($this->returnValue('raw_tooltip'));

        $this->mockMethods($this->form, ['get', 'getConfig', 'getOption'], $formTooltipValue);
        $this->mockMethods($this->configProvider, ['getConfig', 'get'], 'raw_tooltip');
        $extension = new TooltipFormExtension($this->fieldProvider, $this->configProvider, $this->translator);
        $extension->buildView(new FormView(), $this->form, $options);
    }

    /**
     * @return array
     */
    public function modifyTooltipFieldProvider()
    {
        return [
            'Value isnt overriden' => [
                'formTooltipValue' => 'Tooltip description',
                'valueIsOverriden' => false
            ],
            'Value is overriden' => [
                'formTooltipValue' => null,
                'valueIsOverriden' => true
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
