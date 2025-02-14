<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Captcha;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FormBundle\Captcha\CaptchaServiceInterface;
use Oro\Bundle\FormBundle\Captcha\CaptchaServiceRegistry;
use Oro\Bundle\FormBundle\Captcha\CaptchaSettingsProvider;
use Oro\Bundle\FormBundle\DependencyInjection\Configuration;
use Oro\Bundle\SecurityBundle\Authentication\Token\AnonymousToken;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class CaptchaSettingsProviderTest extends TestCase
{
    private ConfigManager|MockObject $configManager;
    private CaptchaServiceRegistry|MockObject $captchaServiceRegistry;
    private CaptchaServiceInterface|MockObject $captchaService;
    private TokenStorageInterface|MockObject $tokenStorage;

    private CaptchaSettingsProvider $captchaSettingsProvider;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->captchaServiceRegistry = $this->createMock(CaptchaServiceRegistry::class);
        $this->captchaService = $this->createMock(CaptchaServiceInterface::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);

        $this->captchaSettingsProvider = new CaptchaSettingsProvider(
            $this->configManager,
            $this->captchaServiceRegistry
        );
        $this->captchaSettingsProvider->setTokenStorage($this->tokenStorage);
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
            ->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                [Configuration::getConfigKey(Configuration::ENABLED_CAPTCHA)],
                [Configuration::getConfigKey(Configuration::USE_CAPTCHA_FOR_LOGGED_IN)]
            )
            ->willReturnOnConsecutiveCalls(true, true);

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

    public function testIsProtectionWhenUserChecksEnabledAndUserNotLoggedInNoToken()
    {
        $this->configManager
            ->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                [Configuration::getConfigKey(Configuration::ENABLED_CAPTCHA)],
                [Configuration::getConfigKey(Configuration::USE_CAPTCHA_FOR_LOGGED_IN)]
            )
            ->willReturnOnConsecutiveCalls(true, false);

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn(null);

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

    public function testIsProtectionWhenUserChecksEnabledAndUserNotLoggedInAnonymousToken()
    {
        $this->configManager
            ->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                [Configuration::getConfigKey(Configuration::ENABLED_CAPTCHA)],
                [Configuration::getConfigKey(Configuration::USE_CAPTCHA_FOR_LOGGED_IN)]
            )
            ->willReturnOnConsecutiveCalls(true, false);

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($this->createMock(AnonymousToken::class));

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

    public function testIsProtectionWhenUserChecksEnabledAndUserLoggedIn()
    {
        $this->configManager
            ->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                [Configuration::getConfigKey(Configuration::ENABLED_CAPTCHA)],
                [Configuration::getConfigKey(Configuration::USE_CAPTCHA_FOR_LOGGED_IN)]
            )
            ->willReturnOnConsecutiveCalls(true, false);

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($this->createMock(UsernamePasswordOrganizationToken::class));

        $this->captchaServiceRegistry
            ->expects($this->never())
            ->method('getCaptchaService');

        $this->captchaService
            ->expects($this->never())
            ->method('isConfigured');

        $this->assertFalse($this->captchaSettingsProvider->isProtectionAvailable());
    }

    public function testIsProtectionAvailableWhenCaptchaEnabledAndServiceNotConfigured()
    {
        $this->configManager
            ->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                [Configuration::getConfigKey(Configuration::ENABLED_CAPTCHA)],
                [Configuration::getConfigKey(Configuration::USE_CAPTCHA_FOR_LOGGED_IN)]
            )
            ->willReturnOnConsecutiveCalls(true, true);

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
