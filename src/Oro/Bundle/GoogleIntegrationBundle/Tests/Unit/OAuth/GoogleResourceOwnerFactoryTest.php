<?php

declare(strict_types=1);

namespace Oro\Bundle\GoogleIntegrationBundle\Tests\Unit\OAuth;

use HWI\Bundle\OAuthBundle\OAuth\RequestDataStorageInterface;
use HWI\Bundle\OAuthBundle\OAuth\ResourceOwner\GoogleResourceOwner;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\GoogleIntegrationBundle\OAuth\GoogleResourceOwnerFactory;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GoogleResourceOwnerFactoryTest extends TestCase
{
    private SymmetricCrypterInterface $crypter;
    private ConfigManager $configManager;
    private HttpClientInterface $httpClient;
    private HttpUtils $httpUtils;
    private RequestDataStorageInterface $storage;

    #[\Override]
    protected function setUp(): void
    {
        $this->crypter = $this->createMock(SymmetricCrypterInterface::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->httpUtils = $this->createMock(HttpUtils::class);
        $this->storage = $this->createMock(RequestDataStorageInterface::class);
    }

    public function testCreateGoogleResourceOwner()
    {
        $this->configManager->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                ['oro_google_integration.client_id'],
                ['oro_google_integration.client_secret']
            )
            ->willReturnOnConsecutiveCalls('clientId', 'encryptedClientSecret');

        $this->crypter->expects($this->once())
            ->method('decryptData')
            ->with('encryptedClientSecret')
            ->willReturn('clientSecret');

        $factory = new GoogleResourceOwnerFactory();

        $resourceOwner = $factory->create(
            $this->crypter,
            $this->configManager,
            $this->httpClient,
            $this->httpUtils,
            $this->storage,
            'google',
            ['client_id' => 'changeMe', 'client_secret' => 'changeMe']
        );

        $this->assertInstanceOf(GoogleResourceOwner::class, $resourceOwner);
        $this->assertEquals('clientId', $resourceOwner->getOption('client_id'));
        $this->assertEquals('clientSecret', $resourceOwner->getOption('client_secret'));
    }

    public function testEmptyClientIdCreateGoogleResourceOwner()
    {
        $this->configManager->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                ['oro_google_integration.client_id'],
                ['oro_google_integration.client_secret']
            )
            ->willReturnOnConsecutiveCalls(null, 'encryptedClientSecret');

        $this->crypter->expects($this->once())
            ->method('decryptData')
            ->with('encryptedClientSecret')
            ->willReturn('clientSecret');

        $factory = new GoogleResourceOwnerFactory();

        $resourceOwner = $factory->create(
            $this->crypter,
            $this->configManager,
            $this->httpClient,
            $this->httpUtils,
            $this->storage,
            'google',
            ['client_id' => 'changeMe', 'client_secret' => 'changeMe']
        );

        $this->assertInstanceOf(GoogleResourceOwner::class, $resourceOwner);
        $this->assertEquals('changeMe', $resourceOwner->getOption('client_id'));
        $this->assertEquals('clientSecret', $resourceOwner->getOption('client_secret'));
    }

    public function testEmptyClientSecretCreateGoogleResourceOwner()
    {
        $this->configManager->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                ['oro_google_integration.client_id'],
                ['oro_google_integration.client_secret']
            )
            ->willReturnOnConsecutiveCalls('clientId', null);

        $this->crypter->expects($this->never())->method('decryptData');

        $factory = new GoogleResourceOwnerFactory();

        $resourceOwner = $factory->create(
            $this->crypter,
            $this->configManager,
            $this->httpClient,
            $this->httpUtils,
            $this->storage,
            'google',
            ['client_id' => 'changeMe', 'client_secret' => 'changeMe']
        );

        $this->assertInstanceOf(GoogleResourceOwner::class, $resourceOwner);
        $this->assertEquals('clientId', $resourceOwner->getOption('client_id'));
        $this->assertEquals('changeMe', $resourceOwner->getOption('client_secret'));
    }
}
