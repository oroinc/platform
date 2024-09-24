<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Captcha\CaptchaServiceInterface;
use Oro\Bundle\FormBundle\Form\Type\ReCaptchaType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;

class ReCaptchaTypeTest extends TypeTestCase
{
    private $captchaService;

    #[\Override]
    protected function setUp(): void
    {
        $this->captchaService = $this->createMock(CaptchaServiceInterface::class);
        parent::setUp();
    }

    #[\Override]
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension([
                new ReCaptchaType($this->captchaService),
            ], [])
        ];
    }

    public function testFinishView()
    {
        $publicKey = 'test_public_key';

        $this->captchaService->expects($this->any())
            ->method('getPublicKey')
            ->willReturn($publicKey);

        $type = new ReCaptchaType($this->captchaService);

        $form = $this->factory->create(ReCaptchaType::class);
        $view = $form->createView();
        $view->vars['full_name'] = 'test_form[captcha]';

        $type->finishView($view, $form, []);

        $this->assertArrayHasKey('data-page-component-module', $view->vars['attr']);
        $this->assertEquals(
            'oroform/js/app/components/captcha-recaptcha-component',
            $view->vars['attr']['data-page-component-module']
        );

        $this->assertArrayHasKey('data-page-component-options', $view->vars['attr']);
        $options = json_decode($view->vars['attr']['data-page-component-options'], true);
        $this->assertEquals($publicKey, $options['site_key']);
        $this->assertEquals('test_form', $options['action']);
    }

    public function testGetParent()
    {
        $type = new ReCaptchaType($this->captchaService);
        $this->assertEquals(HiddenType::class, $type->getParent());
    }

    public function testGetName()
    {
        $type = new ReCaptchaType($this->captchaService);
        $this->assertEquals('oro_recaptcha_token', $type->getName());
    }

    public function testGetBlockPrefix()
    {
        $type = new ReCaptchaType($this->captchaService);
        $this->assertEquals('oro_recaptcha_token', $type->getBlockPrefix());
    }
}
