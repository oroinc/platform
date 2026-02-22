<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Twig;

use Oro\Bundle\FormBundle\Captcha\CaptchaSettingsProviderInterface;
use Oro\Bundle\FormBundle\Form\Type\CaptchaType;
use Oro\Bundle\FormBundle\Twig\FormExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormRendererInterface;
use Symfony\Component\Form\FormView;

class FormExtensionTest extends TestCase
{
    use TwigExtensionTestCaseTrait;

    private FormFactoryInterface&MockObject $formFactory;
    private CaptchaSettingsProviderInterface&MockObject $captchaSettingsProvider;
    private FormRendererInterface&MockObject $formRenderer;
    private FormExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->formRenderer = $this->createMock(FormRendererInterface::class);
        $this->captchaSettingsProvider = $this->createMock(CaptchaSettingsProviderInterface::class);

        $container = self::getContainerBuilder()
            ->add(FormFactoryInterface::class, $this->formFactory)
            ->add('twig.form.renderer', $this->formRenderer)
            ->add('oro_form.captcha.settings_provider', $this->captchaSettingsProvider)
            ->getContainer($this);

        $this->extension = new FormExtension($container);
    }

    public function testIsFormProtectedWithCaptcha(): void
    {
        $formName = 'test_form';

        $this->captchaSettingsProvider->expects($this->once())
            ->method('isProtectionAvailable')
            ->willReturn(true);

        $this->captchaSettingsProvider->expects($this->once())
            ->method('isFormProtected')
            ->with($formName)
            ->willReturn(true);

        $this->assertTrue($this->extension->isFormProtectedWithCaptcha($formName));
    }

    public function testGetCaptchaFormElement(): void
    {
        $captchaForm = $this->createMock(FormInterface::class);

        $this->formFactory->expects($this->once())
            ->method('createNamed')
            ->with('captcha', CaptchaType::class)
            ->willReturn($captchaForm);

        $formView = $this->createMock(FormView::class);
        $captchaForm->expects($this->once())
            ->method('createView')
            ->willReturn($formView);

        $this->assertSame($formView, $this->extension->getCaptchaFormElement('captcha'));
    }
}
