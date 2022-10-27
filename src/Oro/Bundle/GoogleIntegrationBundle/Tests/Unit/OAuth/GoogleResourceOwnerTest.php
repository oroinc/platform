<?php

namespace Oro\Bundle\GoogleIntegrationBundle\Tests\Unit\OAuth;

use Http\Client\Common\HttpMethodsClient;
use HWI\Bundle\OAuthBundle\OAuth\RequestDataStorageInterface;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\GoogleIntegrationBundle\OAuth\GoogleResourceOwner;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Symfony\Component\Security\Http\HttpUtils;

class GoogleResourceOwnerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigManager */
    private $configManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject|SymmetricCrypterInterface */
    private $crypter;

    /** @var GoogleResourceOwner */
    private $resourceOwner;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->crypter = $this->createMock(SymmetricCrypterInterface::class);

        $this->resourceOwner = new GoogleResourceOwner(
            new HttpMethodsClient(
                $this->createMock(ClientInterface::class),
                $this->createMock(RequestFactoryInterface::class)
            ),
            $this->createMock(HttpUtils::class),
            [
                'client_id'     => 'changeMe',
                'client_secret' => 'changeMe',
            ],
            'google',
            $this->createMock(RequestDataStorageInterface::class)
        );
        $this->resourceOwner->setCrypter($this->crypter);
    }

    public function testConfigureCredentialsShouldSetClientIdAndSecret()
    {
        $this->assertNotEquals('clientId', $this->resourceOwner->getOption('client_id'));
        $this->assertNotEquals('clientSecret', $this->resourceOwner->getOption('client_secret'));

        $this->configManager->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                ['oro_google_integration.client_id', false, false, null, 'clientId'],
                ['oro_google_integration.client_secret', false, false, null, 'encryptedClientSecret']
            ]);
        $this->crypter->expects($this->once())
            ->method('decryptData')
            ->with('encryptedClientSecret')
            ->willReturn('clientSecret');

        $this->resourceOwner->configureCredentials($this->configManager);
        $this->assertEquals('clientId', $this->resourceOwner->getOption('client_id'));
        $this->assertEquals('clientSecret', $this->resourceOwner->getOption('client_secret'));
    }
}
