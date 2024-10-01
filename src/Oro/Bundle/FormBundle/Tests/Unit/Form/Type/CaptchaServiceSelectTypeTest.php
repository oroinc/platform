<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Captcha\CaptchaServiceRegistry;
use Oro\Bundle\FormBundle\Form\Type\CaptchaServiceSelectType;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\ChoiceList\Loader\CallbackChoiceLoader;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CaptchaServiceSelectTypeTest extends TypeTestCase
{
    private CaptchaServiceRegistry|MockObject $serviceRegistry;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->serviceRegistry = $this->createMock(CaptchaServiceRegistry::class);
    }

    public function testConfigureOptions()
    {
        $services = ['service1', 'service2'];

        $this->serviceRegistry->expects($this->once())
            ->method('getCaptchaServiceAliases')
            ->willReturn($services);

        $type = new CaptchaServiceSelectType($this->serviceRegistry);
        $resolver = new OptionsResolver();
        $type->configureOptions($resolver);

        $options = $resolver->resolve();

        $choices = [
            'oro_form.captcha.service_name.service1' => 'service1',
            'oro_form.captcha.service_name.service2' => 'service2'
        ];

        $this->assertInstanceOf(CallbackChoiceLoader::class, $options['choice_loader']);
        $this->assertEquals($choices, $options['choice_loader']->loadChoiceList()->getStructuredValues());
    }

    public function testGetParent()
    {
        $type = new CaptchaServiceSelectType($this->serviceRegistry);
        $this->assertEquals(ChoiceType::class, $type->getParent());
    }

    public function testGetBlockPrefix()
    {
        $type = new CaptchaServiceSelectType($this->serviceRegistry);
        $this->assertEquals('oro_captcha_service_select', $type->getBlockPrefix());
    }
}
