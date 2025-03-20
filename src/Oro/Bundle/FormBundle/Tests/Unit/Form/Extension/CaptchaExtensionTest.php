<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\FormBundle\Captcha\CaptchaSettingsProviderInterface;
use Oro\Bundle\FormBundle\Form\Extension\CaptchaExtension;
use Oro\Bundle\FormBundle\Form\Type\CaptchaType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CaptchaExtensionTest extends TestCase
{
    private CaptchaSettingsProviderInterface|MockObject $captchaSettingsProvider;
    private CaptchaExtension $extension;

    protected function setUp(): void
    {
        $this->captchaSettingsProvider = $this->createMock(CaptchaSettingsProviderInterface::class);

        $this->extension = new CaptchaExtension($this->captchaSettingsProvider);
    }

    public function testConfigureOptions()
    {
        $resolver = new OptionsResolver();
        $this->extension->configureOptions($resolver);

        $this->assertTrue($resolver->hasDefault('captcha_protection_enabled'));
    }

    public function testBuildFormHasCaptcha()
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->once())
            ->method('has')
            ->with('captcha')
            ->willReturn(true);

        $builder->expects($this->never())
            ->method('addEventListener');

        $this->extension->buildForm($builder, []);
    }

    public function testBuildFormProtectionInactive()
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->once())
            ->method('has')
            ->with('captcha')
            ->willReturn(false);

        $this->captchaSettingsProvider->expects($this->any())
            ->method('isProtectionAvailable')
            ->willReturn(false);

        $builder->expects($this->never())
            ->method('addEventListener');

        $this->extension->buildForm($builder, []);
    }

    public function testBuildFormNotProtectedForm()
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->any())
            ->method('getName')
            ->willReturn('form_name');
        $builder->expects($this->once())
            ->method('has')
            ->with('captcha')
            ->willReturn(false);

        $this->captchaSettingsProvider->expects($this->any())
            ->method('isProtectionAvailable')
            ->willReturn(true);

        $this->captchaSettingsProvider->expects($this->once())
            ->method('isFormProtected')
            ->with('form_name')
            ->willReturn(false);

        $builder->expects($this->never())
            ->method('addEventListener');

        $this->extension->buildForm($builder, []);
    }

    public function testBuildFormProtectedForm()
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->any())
            ->method('getName')
            ->willReturn('form_name');
        $builder->expects($this->once())
            ->method('has')
            ->with('captcha')
            ->willReturn(false);

        $this->captchaSettingsProvider->expects($this->any())
            ->method('isProtectionAvailable')
            ->willReturn(true);

        $this->captchaSettingsProvider->expects($this->once())
            ->method('isFormProtected')
            ->with('form_name')
            ->willReturn(true);

        $this->assertCaptchaFieldAdded($builder);

        $this->extension->buildForm($builder, []);
    }

    public function testBuildFormWithCaptchaEnabledVaiOption()
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->any())
            ->method('getName')
            ->willReturn('form_name');
        $builder->expects($this->once())
            ->method('has')
            ->with('captcha')
            ->willReturn(false);

        $this->captchaSettingsProvider->expects($this->any())
            ->method('isProtectionAvailable')
            ->willReturn(true);

        $this->captchaSettingsProvider->expects($this->never())
            ->method('isFormProtected');

        $this->assertCaptchaFieldAdded($builder);

        $this->extension->buildForm($builder, ['captcha_protection_enabled' => true]);
    }

    private function assertCaptchaFieldAdded(FormBuilderInterface|MockObject $builder): void
    {
        $builder->expects($this->once())
            ->method('addEventListener')
            ->willReturnCallback(function ($eventName, $callback) use ($builder) {
                $form = $this->createMock(FormInterface::class);
                $form->expects($this->once())
                    ->method('add')
                    ->with(
                        'captcha',
                        CaptchaType::class,
                        [
                            'label' => null,
                            'required' => false,
                            'mapped' => false,
                            'data' => false
                        ]
                    );

                $event = new FormEvent($form, []);
                $callback($event);

                return $builder;
            });
    }
}
