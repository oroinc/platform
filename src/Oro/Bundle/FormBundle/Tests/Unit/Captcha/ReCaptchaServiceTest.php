<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Captcha;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FormBundle\Captcha\ReCaptcha\ClientInterface;
use Oro\Bundle\FormBundle\Captcha\ReCaptchaClientFactory;
use Oro\Bundle\FormBundle\Captcha\ReCaptchaService;
use Oro\Bundle\FormBundle\DependencyInjection\Configuration;
use Oro\Bundle\FormBundle\Form\Type\ReCaptchaType;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class ReCaptchaServiceTest extends TestCase
{
    private ReCaptchaClientFactory|MockObject $reCaptchaClientFactory;
    private ConfigManager|MockObject $configManager;
    private RequestStack|MockObject $requestStack;
    private SymmetricCrypterInterface|MockObject $crypter;
    private ReCaptchaService $captchaService;

    protected function setUp(): void
    {
        $this->reCaptchaClientFactory = $this->createMock(ReCaptchaClientFactory::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->crypter = $this->createMock(SymmetricCrypterInterface::class);

        $this->captchaService = new ReCaptchaService(
            $this->reCaptchaClientFactory,
            $this->configManager,
            $this->requestStack,
            $this->crypter
        );
    }

    public function testIsConfiguredReturnsTrueWhenKeysArePresent()
    {
        $this->configManager->expects($this->any())
            ->method('get')
            ->willReturnMap([
                [Configuration::getConfigKey(Configuration::RECAPTCHA_PUBLIC_KEY), false, false, null, 'publicKey'],
                [
                    Configuration::getConfigKey(Configuration::RECAPTCHA_PRIVATE_KEY),
                    false,
                    false,
                    null,
                    'encryptedPrivateKey'
                ],
                [Configuration::getConfigKey(Configuration::RECAPTCHA_MINIMAL_ALLOWED_SCORE), false, false, null, 0.5]
            ]);

        $this->crypter->expects($this->once())
            ->method('decryptData')
            ->with('encryptedPrivateKey')
            ->willReturn('privateKey');

        $this->assertTrue($this->captchaService->isConfigured());
    }

    public function testIsConfiguredReturnsFalseWhenKeysAreAbsent()
    {
        $this->configManager->expects($this->any())
            ->method('get')
            ->willReturn(null);

        $this->assertFalse($this->captchaService->isConfigured());
    }

    /**
     * @dataProvider verificationDataProvider
     */
    public function testIsVerified(bool $isSuccess)
    {
        $secret = 'captchaResponseValue';

        $threshold = 0.5;
        $this->configManager->expects($this->any())
            ->method('get')
            ->willReturnMap([
                [Configuration::getConfigKey(Configuration::RECAPTCHA_PUBLIC_KEY), false, false, null, 'publicKey'],
                [
                    Configuration::getConfigKey(Configuration::RECAPTCHA_PRIVATE_KEY),
                    false,
                    false,
                    null,
                    'encryptedPrivateKey'
                ],
                [
                    Configuration::getConfigKey(Configuration::RECAPTCHA_MINIMAL_ALLOWED_SCORE),
                    false,
                    false,
                    null,
                    $threshold
                ],
                ['oro_ui.application_url', false, false, null, 'http://mysite.com'],
            ]);

        $this->crypter->expects($this->once())
            ->method('decryptData')
            ->with('encryptedPrivateKey')
            ->willReturn('privateKey');

        $request = $this->createMock(Request::class);
        $request->expects($this->once())
            ->method('getClientIp')
            ->willReturn('127.0.0.1');
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $reCaptchaMock = $this->createMock(ClientInterface::class);
        $reCaptchaMock->expects($this->once())
            ->method('setExpectedHostname')
            ->with('mysite.com')
            ->willReturnSelf();
        $reCaptchaMock->expects($this->once())
            ->method('setScoreThreshold')
            ->with($threshold)
            ->willReturnSelf();
        $reCaptchaMock->expects($this->once())
            ->method('verify')
            ->with($secret, '127.0.0.1')
            ->willReturn($isSuccess);

        $this->reCaptchaClientFactory->expects($this->once())
            ->method('create')
            ->with('privateKey')
            ->willReturn($reCaptchaMock);

        $this->assertEquals($isSuccess, $this->captchaService->isVerified($secret));
    }

    public function verificationDataProvider(): array
    {
        return [
            [true],
            [false]
        ];
    }

    public function testGetFormTypeReturnsReCaptchaType()
    {
        $this->assertEquals(ReCaptchaType::class, $this->captchaService->getFormType());
    }
}
