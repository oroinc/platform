<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Provider\Rest\Client\Guzzle;

use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\Guzzle\GuzzleRestClient;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\Guzzle\GuzzleRestClientFactory;

class GuzzleRestClientFactoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var GuzzleRestClientFactory */
    protected $factory;

    protected function setUp(): void
    {
        $this->factory = new GuzzleRestClientFactory();
    }

    public function testCreateRestClientWorks()
    {
        $baseUrl = 'https://google.com/api';
        $options = ['auth' => ['username', 'password', 'basic']];
        $client = $this->factory->createRestClient($baseUrl, $options);

        static::assertInstanceOf(GuzzleRestClient::class, $client);

        $baseUrlProperty = new \ReflectionProperty(GuzzleRestClient::class, 'baseUrl');
        $baseUrlProperty->setAccessible(true);
        static::assertEquals($baseUrl, $baseUrlProperty->getValue($client));

        $defaultOptionsProperty = new \ReflectionProperty(GuzzleRestClient::class, 'defaultOptions');
        $defaultOptionsProperty->setAccessible(true);
        static::assertEquals($options, $defaultOptionsProperty->getValue($client));
    }
}
