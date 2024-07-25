<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Captcha;

use GuzzleHttp\ClientInterface as HTTPClientInterface;
use GuzzleHttp\Psr7\Response;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FormBundle\Captcha\TurnstileCaptchaService;
use Oro\Bundle\FormBundle\DependencyInjection\Configuration;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class TurnstileCaptchaServiceTest extends TestCase
{
    private HTTPClientInterface|MockObject $httpClient;
    private LoggerInterface|MockObject $logger;
    private ConfigManager|MockObject $configManager;
    private SymmetricCrypterInterface|MockObject $crypter;
    private RequestStack|MockObject $requestStack;
    private Request|MockObject $request;

    private TurnstileCaptchaService $captchaService;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HTTPClientInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->crypter = $this->createMock(SymmetricCrypterInterface::class);
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->request = $this->createMock(Request::class);

        $this->captchaService = new TurnstileCaptchaService(
            $this->httpClient,
            $this->logger,
            $this->configManager,
            $this->crypter,
            $this->requestStack
        );
    }

    public function testIsConfiguredReturnsTrueWhenKeysArePresent()
    {
        $this->configManager->expects($this->any())
            ->method('get')
            ->willReturnMap([
                [Configuration::getConfigKey(Configuration::TURNSTILE_PUBLIC_KEY), false, false, null, 'publicKey'],
                [
                    Configuration::getConfigKey(Configuration::TURNSTILE_PRIVATE_KEY),
                    false,
                    false,
                    null,
                    'encryptedPrivateKey'
                ]
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

    public function testIsVerifiedReturnsTrueWhenVerificationSucceeds()
    {
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($this->request);

        $this->request->expects($this->once())
            ->method('getClientIp')
            ->willReturn('127.0.0.1');

        $this->configManager->expects($this->any())
            ->method('get')
            ->with(Configuration::getConfigKey(Configuration::TURNSTILE_PRIVATE_KEY))
            ->willReturn('encryptedPrivateKey');

        $this->crypter->expects($this->once())
            ->method('decryptData')
            ->with('encryptedPrivateKey')
            ->willReturn('privateKey');

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('POST', 'https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'form_params' => [
                    'secret' => 'privateKey',
                    'response' => 'responseValue',
                    'remoteip' => '127.0.0.1'
                ]
            ])
            ->willReturn(new Response(200, [], json_encode(['success' => true])));

        $this->assertTrue($this->captchaService->isVerified('responseValue'));
    }

    public function testIsVerifiedReturnsFalseWhenVerificationFails()
    {
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($this->request);

        $this->request->expects($this->once())
            ->method('getClientIp')
            ->willReturn('127.0.0.1');

        $this->configManager->expects($this->any())
            ->method('get')
            ->with(Configuration::getConfigKey(Configuration::TURNSTILE_PRIVATE_KEY))
            ->willReturn('encryptedPrivateKey');

        $this->crypter->expects($this->once())
            ->method('decryptData')
            ->with('encryptedPrivateKey')
            ->willReturn('privateKey');

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('POST', 'https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'form_params' => [
                    'secret' => 'privateKey',
                    'response' => 'responseValue',
                    'remoteip' => '127.0.0.1'
                ]
            ])
            ->willReturn(new Response(200, [], json_encode(['success' => false])));

        $this->assertFalse($this->captchaService->isVerified('responseValue'));
    }

    public function testIsVerifiedReturnsFalseWhenExceptionIsThrown()
    {
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($this->request);

        $this->request->expects($this->once())
            ->method('getClientIp')
            ->willReturn('127.0.0.1');

        $this->configManager->expects($this->any())
            ->method('get')
            ->with(Configuration::getConfigKey(Configuration::TURNSTILE_PRIVATE_KEY))
            ->willReturn('encryptedPrivateKey');

        $this->crypter->expects($this->once())
            ->method('decryptData')
            ->with('encryptedPrivateKey')
            ->willReturn('privateKey');

        $exception = new \Exception('exception');
        $this->httpClient->expects($this->once())
            ->method('request')
            ->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('warning')
            ->with(
                'Unable to verify CAPTCHA',
                ['exception' => $exception]
            );

        $this->assertFalse($this->captchaService->isVerified('responseValue'));
    }
}
