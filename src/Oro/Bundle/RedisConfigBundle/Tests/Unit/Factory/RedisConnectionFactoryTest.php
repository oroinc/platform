<?php

namespace Oro\Bundle\RedisConfigBundle\Tests\Unit\Factory;

use Oro\Bundle\RedisConfigBundle\Factory\RedisConnectionFactory;
use Oro\Bundle\RedisConfigBundle\Predis\Client;
use Oro\Bundle\RedisConfigBundle\Predis\Configuration\IpAddressProvider;
use Oro\Bundle\RedisConfigBundle\Predis\Configuration\Options;
use Oro\Bundle\RedisConfigBundle\Predis\Connection\Aggregate\SentinelReplication;
use Oro\Bundle\RedisConfigBundle\Predis\Connection\StreamConnection as OroStreamConnection;
use PHPUnit\Framework\TestCase;
use Predis\Connection\Aggregate\MasterSlaveReplication;
use Predis\Connection\StreamConnection;
use Symfony\Component\Cache\Exception\InvalidArgumentException;

class RedisConnectionFactoryTest extends TestCase
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
    ): void {
        $ipServerProvider = new IpAddressProvider($serverIpAddress);
        $factory = new RedisConnectionFactory($ipServerProvider);
        $client = $factory($dsn);

        $connection = $client->getConnection();
        $this->assertInstanceOf(SentinelReplication::class, $connection);

        $preferSlaveProperty = new \ReflectionProperty(SentinelReplication::class, 'preferSlave');
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

    /**
     * Predis creates the connections to the master and the replicas that a sentinel returns from these parameters.
     *
     * @dataProvider tlsDsnDataProvider
     */
    public function testTlsDsnPassesTheSchemeAndTheSslOptionsToTheDefaultParameters(
        string $dsn,
        ?array $expectedSsl
    ): void {
        $factory = new RedisConnectionFactory(new IpAddressProvider());
        $client = $factory($dsn);

        $defaultParameters = $client->getOptions()->connections->getDefaultParameters();
        $this->assertSame('tls', $defaultParameters['scheme']);
        $this->assertSame($expectedSsl, $defaultParameters['ssl'] ?? null);
    }

    public function tlsDsnDataProvider(): array
    {
        return [
            'sentinel with a CA file and a boolean option' => [
                'rediss://localhost:26379?redis_sentinel=monitor&dbindex=1'
                    . '&ssl[cafile]=/etc/ssl/ca.pem&ssl[verify_peer_name]=0',
                ['cafile' => '/etc/ssl/ca.pem', 'verify_peer_name' => false],
            ],
            'standalone with a client certificate' => [
                'rediss://localhost:6380/1?ssl[local_cert]=/etc/ssl/client.pem&ssl[local_pk]=/etc/ssl/client.key'
                    . '&ssl[verify_peer]=true',
                ['local_cert' => '/etc/ssl/client.pem', 'local_pk' => '/etc/ssl/client.key', 'verify_peer' => true],
            ],
            'password with characters that break parse_url()' => [
                'rediss://:p?ss#w@localhost:6380/1?ssl[cafile]=/etc/ssl/ca.pem',
                ['cafile' => '/etc/ssl/ca.pem'],
            ],
            'replication with a CA file' => [
                'rediss://localhost:6379?host[localhost:6380]&dbindex=1&cluster=predis&ssl[cafile]=/etc/ssl/ca.pem',
                ['cafile' => '/etc/ssl/ca.pem'],
            ],
            'no ssl options' => [
                'rediss://localhost:26379?redis_sentinel=monitor',
                null,
            ],
        ];
    }

    public function testPlainDsnDoesNotChangeTheDefaultParameters(): void
    {
        $factory = new RedisConnectionFactory(new IpAddressProvider());
        $client = $factory('redis://localhost:26379?redis_sentinel=monitor&ssl[cafile]=/etc/ssl/ca.pem');

        $defaultParameters = $client->getOptions()->connections->getDefaultParameters();
        $this->assertArrayNotHasKey('scheme', $defaultParameters);
        $this->assertArrayNotHasKey('ssl', $defaultParameters);
    }

    /**
     * @dataProvider invalidBooleanDsnDataProvider
     */
    public function testInvalidBooleanSslOptionIsRejected(string $dsn, string $message): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        (new RedisConnectionFactory(new IpAddressProvider()))($dsn);
    }

    public function invalidBooleanDsnDataProvider(): array
    {
        return [
            'typo' => [
                'rediss://localhost:6380/1?ssl[verify_peer]=ture',
                'The ssl[verify_peer] option of the Redis DSN must be 1, 0, true, false, yes, no, on or off.',
            ],
            'empty value' => [
                'rediss://localhost:26379?redis_sentinel=monitor&ssl[verify_peer_name]=',
                'The ssl[verify_peer_name] option of the Redis DSN must be 1, 0, true, false, yes, no, on or off.',
            ],
            'plain scheme' => [
                'redis://localhost:6379/1?ssl[verify_peer]=enabled',
                'The ssl[verify_peer] option of the Redis DSN must be 1, 0, true, false, yes, no, on or off.',
            ],
        ];
    }

    public function testValkeysDsnIsTls(): void
    {
        $client = (new RedisConnectionFactory(new IpAddressProvider()))('valkeys://localhost:6380/1');

        $this->assertSame('tls', $client->getOptions()->connections->getDefaultParameters()['scheme']);
    }

    public function testTlsDsnUsesTheConnectionWithItsOwnStreamContext(): void
    {
        $client = (new RedisConnectionFactory(new IpAddressProvider()))('rediss://localhost:6380/1');

        $this->assertInstanceOf(OroStreamConnection::class, $client->getConnection());
    }

    public function testPlainDsnUsesThePredisConnection(): void
    {
        $client = (new RedisConnectionFactory(new IpAddressProvider()))('redis://localhost:6379/1');

        $this->assertSame(StreamConnection::class, \get_class($client->getConnection()));
    }
}
