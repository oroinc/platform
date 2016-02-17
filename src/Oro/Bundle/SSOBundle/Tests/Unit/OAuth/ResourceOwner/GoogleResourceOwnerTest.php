<?php

namespace Oro\Bundle\SSOBundle\Tests\OAuth\ResourceOwner;

use Oro\Bundle\SSOBundle\OAuth\ResourceOwner\GoogleResourceOwner;

class GoogleResourceOwnerTest extends \PHPUnit_Framework_TestCase
{
    private $cm;
    private $googleResourceOwner;

    public function setUp()
    {
        $this->cm = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $httpClient = $this->getMock('Buzz\Client\ClientInterface');
        $httpUtils = $this->getMock('Symfony\Component\Security\Http\HttpUtils');
        $options = [
            'client_id' => 'changeMe',
            'client_secret' => 'changeMe',
        ];
        $name = 'google';
        $storage = $this->getMock('HWI\Bundle\OAuthBundle\OAuth\RequestDataStorageInterface');

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
