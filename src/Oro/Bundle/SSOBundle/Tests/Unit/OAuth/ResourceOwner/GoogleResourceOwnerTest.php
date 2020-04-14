<?php

namespace Oro\Bundle\SSOBundle\Tests\OAuth\ResourceOwner;

use Http\Client\Common\HttpMethodsClient;
use HWI\Bundle\OAuthBundle\OAuth\RequestDataStorageInterface;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\SSOBundle\OAuth\ResourceOwner\GoogleResourceOwner;
use Symfony\Component\Security\Http\HttpUtils;

class GoogleResourceOwnerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigManager */
    private $cm;

    /** @var GoogleResourceOwner */
    private $googleResourceOwner;

    protected function setUp(): void
    {
        $this->cm = $this->createMock(ConfigManager::class);

        $httpClient = $this->createMock(HttpMethodsClient::class);
        $httpUtils = $this->createMock(HttpUtils::class);
        $options = [
            'client_id' => 'changeMe',
            'client_secret' => 'changeMe',
        ];
        $name = 'google';
        $storage = $this->createMock(RequestDataStorageInterface::class);

        $this->googleResourceOwner = new GoogleResourceOwner($httpClient, $httpUtils, $options, $name, $storage);
    }

    public function testConfigureCredentialsShouldSetClientIdAndSecret()
    {
        // guards
        $this->assertNotEquals('clientId', $this->googleResourceOwner->getOption('client_id'));
        $this->assertNotEquals('clientSecret', $this->googleResourceOwner->getOption('client_secret'));

        $this->cm
            ->expects($this->at(0))
            ->method('get')
            ->with('oro_google_integration.client_id')
            ->will($this->returnValue('clientId'));

        $this->cm
            ->expects($this->at(1))
            ->method('get')
            ->with('oro_google_integration.client_secret')
            ->will($this->returnValue('clientSecret'));

        $this->googleResourceOwner->configureCredentials($this->cm);
        $this->assertEquals('clientId', $this->googleResourceOwner->getOption('client_id'));
        $this->assertEquals('clientSecret', $this->googleResourceOwner->getOption('client_secret'));
    }
}
