<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Captcha;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FormBundle\Captcha\CaptchaServiceInterface;
use Oro\Bundle\FormBundle\Captcha\CaptchaServiceRegistry;
use Oro\Bundle\FormBundle\DependencyInjection\Configuration;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CaptchaServiceRegistryTest extends TestCase
{
    private ConfigManager&MockObject $configManager;
    private CaptchaServiceInterface&MockObject $captchaService;
    private CaptchaServiceRegistry $captchaServiceRegistry;

    #[\Override]
    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->captchaService = $this->createMock(CaptchaServiceInterface::class);

        $this->captchaServiceRegistry = new CaptchaServiceRegistry(
            $this->configManager,
            ['service1' => $this->captchaService]
        );
    }

    public function testGetCaptchaServiceAliases(): void
    {
        $aliases = $this->captchaServiceRegistry->getCaptchaServiceAliases();

        $this->assertIsArray($aliases);
        $this->assertCount(1, $aliases);
        $this->assertContains('service1', $aliases);
    }

    public function testGetCaptchaService(): void
    {
        $this->configManager->expects($this->once())
            ->method('get')
            ->with(Configuration::getConfigKey(Configuration::CAPTCHA_SERVICE))
            ->willReturn('service1');

        $service = $this->captchaServiceRegistry->getCaptchaService();

        $this->assertSame($this->captchaService, $service);
    }

    public function testGetCaptchaServiceThrowsExceptionWhenServiceNotFound(): void
    {
        $this->configManager->expects($this->once())
            ->method('get')
            ->with(Configuration::getConfigKey(Configuration::CAPTCHA_SERVICE))
            ->willReturn('service3');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Captcha service "service3" not found');

        $this->captchaServiceRegistry->getCaptchaService();
    }
}
