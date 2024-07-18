<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Captcha;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FormBundle\Captcha\CaptchaServiceInterface;
use Oro\Bundle\FormBundle\Captcha\CaptchaServiceRegistry;
use Oro\Bundle\FormBundle\Captcha\CaptchaSettingsProvider;
use Oro\Bundle\FormBundle\DependencyInjection\Configuration;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CaptchaSettingsProviderTest extends TestCase
{
    private ConfigManager|MockObject $configManager;
    private CaptchaServiceRegistry|MockObject $captchaServiceRegistry;
    private CaptchaServiceInterface|MockObject $captchaService;
    private CaptchaSettingsProvider $captchaSettingsProvider;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->captchaServiceRegistry = $this->createMock(CaptchaServiceRegistry::class);
        $this->captchaService = $this->createMock(CaptchaServiceInterface::class);

        $this->captchaSettingsProvider = new CaptchaSettingsProvider(
            $this->configManager,
            $this->captchaServiceRegistry
        );
    }

    public function testIsProtectionAvailableWhenCaptchaDisabled()
    {
        $this->configManager
            ->expects($this->once())
            ->method('get')
            ->with(Configuration::getConfigKey(Configuration::ENABLED_CAPTCHA))
            ->willReturn(false);

        $this->assertFalse($this->captchaSettingsProvider->isProtectionAvailable());
    }

    public function testIsProtectionAvailableWhenCaptchaEnabledAndServiceConfigured()
    {
        $this->configManager
            ->expects($this->once())
            ->method('get')
            ->with(Configuration::getConfigKey(Configuration::ENABLED_CAPTCHA))
            ->willReturn(true);

        $this->captchaServiceRegistry
            ->expects($this->once())
            ->method('getCaptchaService')
            ->willReturn($this->captchaService);

        $this->captchaService
            ->expects($this->once())
            ->method('isConfigured')
            ->willReturn(true);

        $this->assertTrue($this->captchaSettingsProvider->isProtectionAvailable());
    }

    public function testIsProtectionAvailableWhenCaptchaEnabledAndServiceNotConfigured()
    {
        $this->configManager
            ->expects($this->once())
            ->method('get')
            ->with(Configuration::getConfigKey(Configuration::ENABLED_CAPTCHA))
            ->willReturn(true);

        $this->captchaServiceRegistry
            ->expects($this->once())
            ->method('getCaptchaService')
            ->willReturn($this->captchaService);

        $this->captchaService
            ->expects($this->once())
            ->method('isConfigured')
            ->willReturn(false);

        $this->assertFalse($this->captchaSettingsProvider->isProtectionAvailable());
    }

    public function testIsFormProtected()
    {
        $formName = 'test_form';
        $protectedForms = ['test_form'];

        $this->configManager
            ->expects($this->once())
            ->method('get')
            ->with(Configuration::getConfigKey(Configuration::CAPTCHA_PROTECTED_FORMS))
            ->willReturn($protectedForms);

        $this->assertTrue($this->captchaSettingsProvider->isFormProtected($formName));
    }

    public function testIsFormProtectedWhenNotProtected()
    {
        $formName = 'test_form';
        $protectedForms = ['another_form'];

        $this->configManager
            ->expects($this->once())
            ->method('get')
            ->with(Configuration::getConfigKey(Configuration::CAPTCHA_PROTECTED_FORMS))
            ->willReturn($protectedForms);

        $this->assertFalse($this->captchaSettingsProvider->isFormProtected($formName));
    }

    public function testGetFormType()
    {
        $formType = 'captcha_form_type';

        $this->captchaServiceRegistry
            ->expects($this->once())
            ->method('getCaptchaService')
            ->willReturn($this->captchaService);

        $this->captchaService
            ->expects($this->once())
            ->method('getFormType')
            ->willReturn($formType);

        $this->assertSame($formType, $this->captchaSettingsProvider->getFormType());
    }
}
