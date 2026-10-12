<?php

namespace Oro\Bundle\RedisConfigBundle\Tests\Unit\Predis\Configuration;

use Oro\Bundle\RedisConfigBundle\Predis\Configuration\IpAddressProvider;
use Oro\Bundle\RedisConfigBundle\Predis\Configuration\Options;
use Oro\Bundle\RedisConfigBundle\Predis\Connection\StreamConnection;
use PHPUnit\Framework\TestCase;
use Predis\Connection\StreamConnection as PredisStreamConnection;

class ConnectionFactoryOptionTest extends TestCase
{
    private function createOptions(array $options = []): Options
    {
        $options = new Options($options);
        $options->setIpAddressProvider(new IpAddressProvider());

        return $options;
    }

    /**
     * @dataProvider tlsSchemeDataProvider
     */
    public function testTlsSchemeCreatesTheConnectionWithItsOwnStreamContext(string $scheme): void
    {
        $factory = $this->createOptions()->connections;

        $this->assertInstanceOf(StreamConnection::class, $factory->create(['scheme' => $scheme]));
    }

    public function tlsSchemeDataProvider(): array
    {
        return [
            'tls' => ['tls'],
            'rediss' => ['rediss'],
        ];
    }

    public function testTcpSchemeKeepsThePredisConnection(): void
    {
        $factory = $this->createOptions()->connections;

        $this->assertSame(PredisStreamConnection::class, \get_class($factory->create(['scheme' => 'tcp'])));
    }

    public function testDefaultParametersAreKept(): void
    {
        $factory = $this->createOptions(['parameters' => ['scheme' => 'tls', 'database' => 2]])->connections;

        $this->assertSame(['scheme' => 'tls', 'database' => 2], $factory->getDefaultParameters());
        $connection = $factory->create(['host' => 'localhost']);
        $this->assertInstanceOf(StreamConnection::class, $connection);
        $this->assertSame(2, $connection->getParameters()->database);
    }

    public function testConnectionsOptionReplacesTheDefault(): void
    {
        $factory = $this->createOptions(['connections' => ['tls' => PredisStreamConnection::class]])->connections;

        $this->assertSame(PredisStreamConnection::class, \get_class($factory->create(['scheme' => 'tls'])));
        $this->assertInstanceOf(StreamConnection::class, $factory->create(['scheme' => 'rediss']));
    }
}
