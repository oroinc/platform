<?php

namespace Oro\Bundle\RedisConfigBundle\Tests\Unit\Factory;

use Oro\Bundle\RedisConfigBundle\Factory\RedisConnectionFactory;
use Oro\Bundle\RedisConfigBundle\Predis\Client;
use Oro\Bundle\RedisConfigBundle\Predis\Configuration\IpAddressProvider;
use Oro\Bundle\RedisConfigBundle\Predis\Configuration\Options;
use Oro\Bundle\RedisConfigBundle\Predis\Connection\Aggregate\SentinelReplication;
use Predis\Connection\Aggregate\MasterSlaveReplication;
use Predis\Connection\StreamConnection;
use Symfony\Component\Cache\Exception\InvalidArgumentException;

class RedisConnectionFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider factoryTypedInstancesDataProvider
     */
    public function testClientFlowProperTypedInstancesCreation(string $dsn, string $expectedConnectionClass): void
    {
        $ipServerProvider = new IpAddressProvider();
        $factory = new RedisConnectionFactory($ipServerProvider);
        $client = $factory($dsn);

        $this->assertInstanceOf(Client::class, $client);
        $options = $client->getOptions();
        $this->assertInstanceOf(Options::class, $options);
        $connection = $client->getConnection();
        $this->assertInstanceOf($expectedConnectionClass, $connection);
    }

    public function factoryTypedInstancesDataProvider(): array
    {
        return [
            [
                'redis://pw@/var/run/redis.sock',
                StreamConnection::class
            ],
            [
                'redis://pw@localhost:26379?redis_sentinel=monitor',
                SentinelReplication::class
            ],
            [
                'redis:?host[localhost:26379]&redis_sentinel=monitor&prefer_slave=192.168.0.1',
                SentinelReplication::class
            ],
            [
                'redis:?host[localhost]&host[localhost:26380]&redis_sentinel=monitor',
                SentinelReplication::class
            ],
            [
                'redis://pw@localhost:6379?host[localhost:6380]&cluster=predis',
                MasterSlaveReplication::class
            ],
            [
                'redis://pw@localhost:6379?host[localhost:6380]&cluster=redis',
                MasterSlaveReplication::class
            ]
        ];
    }

    public function testNullClientGeneration(): void
    {
        $ipServerProvider = new IpAddressProvider();
        $factory = new RedisConnectionFactory($ipServerProvider);
        $client = $factory('non-redis:');
        $this->assertNull($client);
    }

    public function testExcepetionPropagationOnClientGeneration(): void
    {
        $ipServerProvider = new IpAddressProvider();
        $factory = new RedisConnectionFactory($ipServerProvider);
        $this->expectException(InvalidArgumentException::class);
        $factory('redis://localhost?redis_cluster=1&redis_sentinel=sentinel');
    }

    /**
     * @dataProvider factoryPreferSlaveParameterDataProvider
     */
    public function testPreferSlaveParameterPropagationAndSetup(
        string $dsn,
        string $serverIpAddress,
        mixed $expectedPreferSlave
    ) {
        $ipServerProvider = new IpAddressProvider($serverIpAddress);
        $factory = new RedisConnectionFactory($ipServerProvider);
        $client = $factory($dsn);

        $connection = $client->getConnection();
        $this->assertInstanceOf(SentinelReplication::class, $connection);

        $preferSlaveProperty = new \ReflectionProperty(SentinelReplication::class, 'preferSlave');
        $preferSlaveProperty->setAccessible(true);
        $this->assertEquals($expectedPreferSlave, $preferSlaveProperty->getValue($connection));
    }

    public function factoryPreferSlaveParameterDataProvider(): array
    {
        return [
            [
                'redis:?host[localhost:26379]&redis_sentinel=monitor',
                '127.0.0.1',
                '127.0.0.1'
            ],
            [
                'redis:?host[localhost:26379]&redis_sentinel=monitor&prefer_slave=192.168.0.1',
                '192.168.0.1',
                '192.168.0.1'
            ],
            [
                'redis:?host[localhost:26379]&redis_sentinel=monitor&prefer_slave[192.168.0.1]=192.168.0.5',
                '192.168.0.1',
                '192.168.0.5'
            ],
            [
                'redis:?host[localhost:26379]&redis_sentinel=monitor&prefer_slave[192.168.0.1]=192.168.0.5' .
                    '&prefer_slave[192.168.0.2]=192.168.0.6&prefer_slave[192.168.0.3]=192.168.0.7',
                '192.168.0.2',
                '192.168.0.6'
            ],
            [
                'redis:?host[localhost:26379]&redis_sentinel=monitor&prefer_slave[192.168.0.1]=192.168.0.5' .
                '&prefer_slave[192.168.0.2]=192.168.0.6&prefer_slave[192.168.0.3]=192.168.0.7',
                '192.168.0.10',
                '127.0.0.1'
            ]
        ];
    }
}
