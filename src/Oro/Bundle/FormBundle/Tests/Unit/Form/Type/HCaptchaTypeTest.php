<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Captcha\CaptchaServiceInterface;
use Oro\Bundle\FormBundle\Form\Type\HCaptchaType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;

class HCaptchaTypeTest extends TypeTestCase
{
    private $captchaService;

    protected function setUp(): void
    {
        $this->captchaService = $this->createMock(CaptchaServiceInterface::class);
        parent::setUp();
    }

    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension([
                new HCaptchaType($this->captchaService),
            ], [])
        ];
    }

    public function testFinishView()
    {
        $publicKey = 'test_public_key';

        $this->captchaService->expects($this->any())
            ->method('getPublicKey')
            ->willReturn($publicKey);

        $type = new HCaptchaType($this->captchaService);

        $form = $this->factory->create(HCaptchaType::class);
        $view = $form->createView();

        $type->finishView($view, $form, []);

        $this->assertArrayHasKey('data-page-component-module', $view->vars['attr']);
        $this->assertEquals(
            'oroform/js/app/components/captcha-hcaptcha-component',
            $view->vars['attr']['data-page-component-module']
        );

        $this->assertArrayHasKey('data-page-component-options', $view->vars['attr']);
        $options = json_decode($view->vars['attr']['data-page-component-options'], true);
        $this->assertEquals($publicKey, $options['site_key']);
    }

    public function testGetParent()
    {
        $type = new HCaptchaType($this->captchaService);
        $this->assertEquals(HiddenType::class, $type->getParent());
    }

    public function testGetName()
    {
        $type = new HCaptchaType($this->captchaService);
        $this->assertEquals('oro_hcaptcha_token', $type->getName());
    }

    public function testGetBlockPrefix()
    {
        $type = new HCaptchaType($this->captchaService);
        $this->assertEquals('oro_hcaptcha_token', $type->getBlockPrefix());
    }
}
