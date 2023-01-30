<?php

declare(strict_types=1);

namespace Oro\Bundle\RedisConfigBundle\Tests\Unit\Provider;

use Oro\Bundle\RedisConfigBundle\Provider\RedisRequirementsProvider;
use Predis\Client;

class RedisRequirementsProviderTest extends \PHPUnit\Framework\TestCase
{
    public function testNoAvailableClients()
    {
        $provider = new RedisRequirementsProvider([]);
        $requirements = $provider->getOroRequirements();

        $this->assertNotNull($requirements);
        $this->assertCount(0, $requirements->all());
    }

    public function testConnectionNotConfigured()
    {
        $provider = new RedisRequirementsProvider([
            'id' => $this->getClient('1.0', false)
        ]);
        $requirements = $provider->getOroRequirements()->all();

        $this->assertFalse($requirements[0]->isFulfilled());
        $this->assertEquals(
            'Connection for "id" service has invalid configuration.',
            $requirements[0]->getTestMessage()
        );
    }

    public function testVersionRequirementIsFulfilled()
    {
        $version = RedisRequirementsProvider::REQUIRED_VERSION;
        $provider = new RedisRequirementsProvider([
            'id' => $this->getClient($version)
        ]);
        $requirements = $provider->getOroRequirements()->all();

        $this->assertTrue($requirements[0]->isFulfilled());
        $this->assertEquals(
            'Connection for "id" service has required Redis version (' . $version . ')',
            $requirements[0]->getTestMessage()
        );
    }

    public function testVersionRequirementNotFulfilled()
    {
        $provider = new RedisRequirementsProvider([
            'id' => $this->getClient('1.0')
        ]);
        $requirements = $provider->getOroRequirements()->all();

        $this->assertFalse($requirements[0]->isFulfilled());
        $this->assertEquals(
            'Redis version of connection for "id" service must be ' .
                RedisRequirementsProvider::REQUIRED_VERSION . ' or higher',
            $requirements[0]->getHelpHtml()
        );
    }

    public function testMultipleClients()
    {
        $provider = new RedisRequirementsProvider([
            'id1' => $this->getClient('1.0'),
            'id2' => $this->getClient('2.0'),
        ]);
        $requirements = $provider->getOroRequirements()->all();

        $this->assertCount(2, $requirements);
    }

    private function getClient(string $version, bool $isConnected = true): Client
    {
        $client = $this->createMock(Client::class);
        $client->expects(self::any())
            ->method('__call')
            ->withAnyParameters()
            ->willReturn(['Server' => ['redis_version' => $version]]);
        $client->expects(self::any())
            ->method('isConnected')
            ->willReturn($isConnected);

        return $client;
    }
}
