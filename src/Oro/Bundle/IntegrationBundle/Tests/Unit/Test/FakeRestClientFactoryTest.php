<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Test;

use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestResponseInterface;
use Oro\Bundle\IntegrationBundle\Test\FakeRestClient;
use Oro\Bundle\IntegrationBundle\Test\FakeRestClientFactory;
use PHPUnit\Framework\TestCase;

/**
 * Class FakeRestClientFactoryTest is unit test for fake factory
 */
class FakeRestClientFactoryTest extends TestCase
{
    private const FAKE_BASE_URL = 'http://localhost';

    private string $fixtureFileName;
    private FakeRestClientFactory $factory;

    #[\Override]
    protected function setUp(): void
    {
        $this->factory = new FakeRestClientFactory();
        $this->fixtureFileName = __DIR__ . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'foo.yml';
    }

    public function testCreateFakeClient(): void
    {
        $this->assertInstanceOf(
            FakeRestClient::class,
            $this->factory->createRestClient(self::FAKE_BASE_URL, [])
        );
    }

    public function testLoadFixtureFromFile(): void
    {
        $fixtures = FakeRestClientFactory::getFixturesFromFile($this->fixtureFileName);
        $this->assertEquals('bar', $fixtures['/foo']['body']);
    }

    public function testCreateClientWithFixtures(): void
    {
        $this->factory->setFixtureFile($this->fixtureFileName);
        $client = $this->factory->createRestClient(self::FAKE_BASE_URL, []);

        $this->assertEquals('bar', $client->getJSON('/foo'));
        $this->assertEquals(['baz'], $client->getJSON('/bar'));
    }

    public function testCreateClientWithDefaultResponse(): void
    {
        $this->factory->setFixtureFile($this->fixtureFileName);
        $client = $this->factory->createRestClient(self::FAKE_BASE_URL, []);

        $this->assertInstanceOf(
            RestResponseInterface::class,
            $client->get('/baz'),
            'Default response should be returned'
        );
        $this->assertEquals(304, $client->get('/baz')->getStatusCode());
    }
}
