<?php

namespace Oro\Bundle\SSOBundle\Tests\OAuth\ResourceOwner;

use Http\Client\Common\HttpMethodsClient;
use HWI\Bundle\OAuthBundle\OAuth\RequestDataStorageInterface;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Oro\Bundle\SSOBundle\OAuth\ResourceOwner\Office365ResourceOwner;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Symfony\Component\Security\Http\HttpUtils;

class Office365ResourceOwnerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigManager */
    private $configManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject|SymmetricCrypterInterface */
    private $crypter;

    /** @var Office365ResourceOwner */
    private $office365ResourceOwner;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);

        $httpClient = new HttpMethodsClient(
            $this->createMock(ClientInterface::class),
            $this->createMock(RequestFactoryInterface::class)
        );
        $httpUtils = $this->createMock(HttpUtils::class);
        $options = [
            'client_id' => 'changeMe',
            'client_secret' => 'changeMe',
        ];
        $name = 'office365';
        $storage = $this->createMock(RequestDataStorageInterface::class);

        $this->crypter = $this->getMockBuilder(SymmetricCrypterInterface::class)->getMock();

        $this->office365ResourceOwner = new Office365ResourceOwner($httpClient, $httpUtils, $options, $name, $storage);
        $this->office365ResourceOwner->setCrypter($this->crypter);
    }

    public function testConfigureCredentialsShouldSetClientIdAndSecret()
    {
        $this->assertNotEquals('clientId', $this->office365ResourceOwner->getOption('client_id'));
        $this->assertNotEquals('clientSecret', $this->office365ResourceOwner->getOption('client_secret'));

        $this->configManager
            ->expects($this->at(0))
            ->method('get')
            ->with('oro_microsoft_integration.client_id')
            ->will($this->returnValue('clientId'));

        $this->crypter->expects($this->any())
            ->method('decryptData')
            ->with('encryptedClientSecret')
            ->willReturn('clientSecret');

        $this->configManager
            ->expects($this->at(1))
            ->method('get')
            ->with('oro_microsoft_integration.client_secret')
            ->will($this->returnValue('encryptedClientSecret'));

        $this->office365ResourceOwner->configureCredentials($this->configManager);
        $this->assertEquals('clientId', $this->office365ResourceOwner->getOption('client_id'));
        $this->assertEquals(
            'clientSecret',
            $this->office365ResourceOwner->getOption('client_secret')
        );
    }
}
