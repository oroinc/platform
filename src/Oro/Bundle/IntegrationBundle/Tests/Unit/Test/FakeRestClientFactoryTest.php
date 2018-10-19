<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Test;

use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestResponseInterface;
use Oro\Bundle\IntegrationBundle\Test\FakeRestClient;
use Oro\Bundle\IntegrationBundle\Test\FakeRestClientFactory;

/**
 * Class FakeRestClientFactoryTest is unit test for fake factory
 */
class FakeRestClientFactoryTest extends \PHPUnit\Framework\TestCase
{
    const FAKE_BASE_URL = 'http://localhost';

    /** @var string */
    private $fixtureFileName;

    /** @var FakeRestClientFactory */
    private $factory;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->factory = new FakeRestClientFactory();
        $this->fixtureFileName = __DIR__ . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'foo.yml';
    }

    public function testCreateFakeClient()
    {
        $this->assertInstanceOf(
            FakeRestClient::class,
            $this->factory->createRestClient(self::FAKE_BASE_URL, [])
        );
    }

    public function testLoadFixtureFromFile()
    {
        $fixtures = FakeRestClientFactory::getFixturesFromFile($this->fixtureFileName);
        $this->assertEquals('bar', $fixtures['/foo']['body']);
    }

    public function testCreateClientWithFixtures()
    {
        $this->factory->setFixtureFile($this->fixtureFileName);
        $client = $this->factory->createRestClient(self::FAKE_BASE_URL, []);

        $this->assertEquals('bar', $client->getJSON('/foo'));
        $this->assertEquals(['baz'], $client->getJSON('/bar'));
    }

    public function testCreateClientWithDefaultResponse()
    {
        $this->factory->setFixtureFile($this->fixtureFileName);
        $client = $this->factory->createRestClient(self::FAKE_BASE_URL, []);

        $this->assertInstanceOf(
            RestResponseInterface::class,
            $client->get('/baz'),
            'Default response should be returned'
        );
        $this->assertEquals(302, $client->get('/baz')->getStatusCode());
    }
}
