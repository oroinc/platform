<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Provider\Rest\Client\Guzzle;

use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\Guzzle\GuzzleRestClient;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\Guzzle\GuzzleRestClientFactory;
use Oro\Component\Testing\ReflectionUtil;

class GuzzleRestClientFactoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var GuzzleRestClientFactory */
    private $factory;

    protected function setUp(): void
    {
        $this->factory = new GuzzleRestClientFactory();
    }

    public function testCreateRestClientWorks()
    {
        $baseUrl = 'https://google.com/api';
        $options = ['auth' => ['username', 'password', 'basic']];
        $client = $this->factory->createRestClient($baseUrl, $options);

        self::assertInstanceOf(GuzzleRestClient::class, $client);
        self::assertEquals($baseUrl, ReflectionUtil::getPropertyValue($client, 'baseUrl'));
        self::assertEquals($options, ReflectionUtil::getPropertyValue($client, 'defaultOptions'));
    }
}
