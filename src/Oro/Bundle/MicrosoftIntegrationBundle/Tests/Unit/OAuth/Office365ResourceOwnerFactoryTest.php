<?php

declare(strict_types=1);

namespace Oro\Bundle\MicrosoftIntegrationBundle\Tests\Unit\OAuth;

use HWI\Bundle\OAuthBundle\OAuth\RequestDataStorageInterface;
use HWI\Bundle\OAuthBundle\OAuth\ResourceOwner\Office365ResourceOwner;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\MicrosoftIntegrationBundle\OAuth\Office365ResourceOwnerFactory;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class Office365ResourceOwnerFactoryTest extends TestCase
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

    public function testCreateOffice365ResourceOwner(): void
    {
        $this->configManager->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                ['oro_microsoft_integration.client_id'],
                ['oro_microsoft_integration.client_secret']
            )
            ->willReturnOnConsecutiveCalls('clientId', 'encryptedClientSecret');

        $this->crypter->expects($this->once())
            ->method('decryptData')
            ->with('encryptedClientSecret')
            ->willReturn('clientSecret');

        $factory = new Office365ResourceOwnerFactory();

        $resourceOwner = $factory->create(
            $this->crypter,
            $this->configManager,
            $this->httpClient,
            $this->httpUtils,
            $this->storage,
            'office365',
            ['client_id' => 'changeMe', 'client_secret' => 'changeMe']
        );

        $this->assertInstanceOf(Office365ResourceOwner::class, $resourceOwner);
        $this->assertEquals('clientId', $resourceOwner->getOption('client_id'));
        $this->assertEquals('clientSecret', $resourceOwner->getOption('client_secret'));
    }

    public function testEmptyClientIdCreateOffice365ResourceOwner(): void
    {
        $this->configManager->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                ['oro_microsoft_integration.client_id'],
                ['oro_microsoft_integration.client_secret']
            )
            ->willReturnOnConsecutiveCalls(null, 'encryptedClientSecret');

        $this->crypter->expects($this->once())
            ->method('decryptData')
            ->with('encryptedClientSecret')
            ->willReturn('clientSecret');

        $factory = new Office365ResourceOwnerFactory();

        $resourceOwner = $factory->create(
            $this->crypter,
            $this->configManager,
            $this->httpClient,
            $this->httpUtils,
            $this->storage,
            'office365',
            ['client_id' => 'changeMe', 'client_secret' => 'changeMe']
        );

        $this->assertInstanceOf(Office365ResourceOwner::class, $resourceOwner);
        $this->assertEquals('changeMe', $resourceOwner->getOption('client_id'));
        $this->assertEquals('clientSecret', $resourceOwner->getOption('client_secret'));
    }

    public function testEmptyClientSecretCreateOffice365ResourceOwner(): void
    {
        $this->configManager->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                ['oro_microsoft_integration.client_id'],
                ['oro_microsoft_integration.client_secret']
            )
            ->willReturnOnConsecutiveCalls('clientId', null);

        $this->crypter->expects($this->never())
            ->method('decryptData');

        $factory = new Office365ResourceOwnerFactory();

        $resourceOwner = $factory->create(
            $this->crypter,
            $this->configManager,
            $this->httpClient,
            $this->httpUtils,
            $this->storage,
            'office365',
            ['client_id' => 'changeMe', 'client_secret' => 'changeMe']
        );

        $this->assertInstanceOf(Office365ResourceOwner::class, $resourceOwner);
        $this->assertEquals('clientId', $resourceOwner->getOption('client_id'));
        $this->assertEquals('changeMe', $resourceOwner->getOption('client_secret'));
    }
}
