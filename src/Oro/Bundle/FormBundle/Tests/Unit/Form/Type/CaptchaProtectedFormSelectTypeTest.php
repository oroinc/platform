<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Captcha\CaptchaProtectedFormsRegistry;
use Oro\Bundle\FormBundle\Form\Type\CaptchaProtectedFormSelectType;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\ChoiceList\Loader\CallbackChoiceLoader;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CaptchaProtectedFormSelectTypeTest extends TypeTestCase
{
    private CaptchaProtectedFormsRegistry&MockObject $formsRegistry;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->formsRegistry = $this->createMock(CaptchaProtectedFormsRegistry::class);
    }

    public function testConfigureOptions()
    {
        $protectedForms = ['form1', 'form2'];

        $this->formsRegistry->expects($this->once())
            ->method('getProtectedForms')
            ->willReturn($protectedForms);

        $type = new CaptchaProtectedFormSelectType($this->formsRegistry);
        $resolver = new OptionsResolver();
        $type->configureOptions($resolver);

        $options = $resolver->resolve();

        $choices = [
            'oro_form.captcha.protected_form_name.form1' => 'form1',
            'oro_form.captcha.protected_form_name.form2' => 'form2'
        ];

        $this->assertInstanceOf(CallbackChoiceLoader::class, $options['choice_loader']);
        $this->assertEquals($choices, $options['choice_loader']->loadChoiceList()->getStructuredValues());
        $this->assertTrue($options['multiple']);
        $this->assertTrue($options['expanded']);
    }

    public function testGetParent()
    {
        $type = new CaptchaProtectedFormSelectType($this->formsRegistry);
        $this->assertEquals(ChoiceType::class, $type->getParent());
    }

    public function testGetBlockPrefix()
    {
        $type = new CaptchaProtectedFormSelectType($this->formsRegistry);
        $this->assertEquals('oro_captcha_protected_form_select', $type->getBlockPrefix());
    }
}
