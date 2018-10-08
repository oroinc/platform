<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\FormBundle\Form\Extension\HintFormExtension;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class HintFormExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $form;

    protected function setUp()
    {
        $this->form = $this->getMockBuilder(Form::class)->disableOriginalConstructor()->getMock();
    }

    public function testConfigureOptions()
    {
        $resolver = $this->getMockBuilder(OptionsResolver::class)
            ->getMock();
        $resolver->expects($this->once())
            ->method('setDefined')
            ->with(['hint']);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(['hint_attr' => ['class' => 'oro-hint']]);

        $extension = new HintFormExtension();
        $extension->configureOptions($resolver);
    }

    public function testBuildView()
    {
        $options = [
            'hint' => 'test',
            'hint_position' => 'after_input',
            'hint_attr' => ['class' => 'test'],
        ];
        $view = new FormView();
        $this->form = $this->getMockBuilder(Form::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->form->method('getParent')->willReturnSelf();

        $extension = new HintFormExtension();
        $extension->buildView($view, $this->form, $options);

        foreach ($options as $option => $value) {
            $this->assertArrayHasKey($option, $view->vars);
            $this->assertEquals($options[$option], $view->vars[$option]);
        }
    }

    public function testParentForm()
    {
        $view = new FormView();
        $this->form->expects($this->any())
            ->method('getParent')
            ->will($this->returnValue(false));

        $extension = new HintFormExtension();
        $extension->buildView($view, $this->form, ['toolbar' => 'test']);
        $this->assertArrayNotHasKey('toolbar', $view->vars);
    }

    protected function tearDown()
    {
        unset($this->form);
    }
}
