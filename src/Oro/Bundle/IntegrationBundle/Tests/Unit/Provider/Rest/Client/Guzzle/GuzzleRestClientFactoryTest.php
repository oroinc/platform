<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Provider\Rest\Client\Guzzle;

use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\Guzzle\GuzzleRestClientFactory;

class GuzzleRestClientFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var GuzzleRestClientFactory
     */
    protected $factory;

    protected function setUp()
    {
        $this->factory = new GuzzleRestClientFactory();
    }

    public function testCreateRestClientWorks()
    {
        $baseUrl = 'https://google.com/api';
        $options = array('auth' => array('username', 'password', 'basic'));
        $client = $this->factory->createRestClient($baseUrl, $options);

        $this->assertInstanceOf(
            'Oro\Bundle\IntegrationBundle\Provider\Rest\Client\Guzzle\GuzzleRestClient',
            $client
        );
        $this->assertAttributeEquals($baseUrl, 'baseUrl', $client);
        $this->assertAttributeEquals($options, 'defaultOptions', $client);
    }
}
