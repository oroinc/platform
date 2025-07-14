<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Provider\Rest\Client\Guzzle;

use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\Guzzle\GuzzleRestClient;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\Guzzle\GuzzleRestClientFactory;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\TestCase;

class GuzzleRestClientFactoryTest extends TestCase
{
    private GuzzleRestClientFactory $factory;

    #[\Override]
    protected function setUp(): void
    {
        $this->factory = new GuzzleRestClientFactory();
    }

    public function testCreateRestClientWorks(): void
    {
        $baseUrl = 'https://google.com/api';
        $options = ['auth' => ['username', 'password', 'basic']];
        $client = $this->factory->createRestClient($baseUrl, $options);

        self::assertInstanceOf(GuzzleRestClient::class, $client);
        self::assertEquals($baseUrl, ReflectionUtil::getPropertyValue($client, 'baseUrl'));
        self::assertEquals($options, ReflectionUtil::getPropertyValue($client, 'defaultOptions'));
    }
}
