<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\FormBundle\Form\Extension\HintFormExtension;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class HintFormExtensionTest extends TestCase
{
    private Form&MockObject $form;

    #[\Override]
    protected function setUp(): void
    {
        $this->form = $this->createMock(Form::class);
    }

    public function testConfigureOptions(): void
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefined')
            ->with(['hint']);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(['hint_attr' => ['class' => 'oro-hint']]);

        $extension = new HintFormExtension();
        $extension->configureOptions($resolver);
    }

    public function testBuildView(): void
    {
        $options = [
            'hint' => 'test',
            'hint_position' => 'after_input',
            'hint_attr' => ['class' => 'test'],
        ];
        $view = new FormView();
        $this->form = $this->createMock(Form::class);
        $this->form->expects($this->any())
            ->method('getParent')
            ->willReturnSelf();

        $extension = new HintFormExtension();
        $extension->buildView($view, $this->form, $options);

        foreach ($options as $option => $value) {
            $this->assertArrayHasKey($option, $view->vars);
            $this->assertEquals($options[$option], $view->vars[$option]);
        }
    }

    public function testParentForm(): void
    {
        $view = new FormView();
        $this->form->expects($this->any())
            ->method('getParent')
            ->willReturn(null);

        $extension = new HintFormExtension();
        $extension->buildView($view, $this->form, ['toolbar' => 'test']);
        $this->assertArrayNotHasKey('toolbar', $view->vars);
    }
}
