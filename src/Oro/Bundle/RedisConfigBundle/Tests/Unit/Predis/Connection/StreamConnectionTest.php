<?php

namespace Oro\Bundle\RedisConfigBundle\Tests\Unit\Predis\Connection;

use Oro\Bundle\RedisConfigBundle\Predis\Connection\StreamConnection;
use PHPUnit\Framework\TestCase;
use Predis\Connection\ConnectionException;
use Predis\Connection\Parameters;

/**
 * The tests connect to a TCP server on the loopback interface through the "tcp" scheme. A TLS handshake is not
 * necessary: the socket of every scheme is opened by the same method, and the stream context of the socket is
 * the subject of the tests.
 */
class StreamConnectionTest extends TestCase
{
    /** @var resource|null */
    private $server;

    #[\Override]
    protected function tearDown(): void
    {
        if (\is_resource($this->server)) {
            fclose($this->server);
        }
    }

    private function startServer(): int
    {
        $this->server = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
        if (!$this->server) {
            $this->markTestSkipped(sprintf('Cannot start a TCP server on 127.0.0.1: %s', $errstr));
        }

        return (int) substr(strrchr(stream_socket_get_name($this->server, false), ':'), 1);
    }

    private function createConnection(int $port, array $parameters = []): StreamConnection
    {
        return new StreamConnection(new Parameters(
            array_merge(['scheme' => 'tcp', 'host' => '127.0.0.1', 'port' => $port], $parameters)
        ));
    }

    public function testSslOptionsOfTheSocketDoNotChangeTheDefaultStreamContext(): void
    {
        $connection = $this->createConnection($this->startServer(), [
            'timeout' => 1.5,
            'read_write_timeout' => 2.5,
            'tcp_nodelay' => true,
        ]);

        $defaultOptions = stream_context_get_options(stream_context_get_default());
        $socket = $connection->getResource();
        stream_context_set_options($socket, ['ssl' => ['verify_peer' => false, 'cafile' => '/etc/ssl/ca.pem']]);

        $socketSslOptions = stream_context_get_options($socket)['ssl'];
        $this->assertFalse($socketSslOptions['verify_peer']);
        $this->assertSame('/etc/ssl/ca.pem', $socketSslOptions['cafile']);
        $this->assertSame($defaultOptions, stream_context_get_options(stream_context_get_default()));
        $connection->disconnect();
    }

    public function testEachSocketHasItsOwnStreamContext(): void
    {
        $port = $this->startServer();
        $firstConnection = $this->createConnection($port);
        $secondConnection = $this->createConnection($port);

        $firstSocket = $firstConnection->getResource();
        $secondSocket = $secondConnection->getResource();
        $secondSocketOptions = stream_context_get_options($secondSocket);
        stream_context_set_options($firstSocket, ['ssl' => ['peer_name' => 'redis.example.com']]);

        $this->assertSame($secondSocketOptions, stream_context_get_options($secondSocket));
        $firstConnection->disconnect();
        $secondConnection->disconnect();
    }

    public function testConnectionErrorThrowsException(): void
    {
        $port = $this->startServer();
        fclose($this->server);

        $this->expectException(ConnectionException::class);

        $this->createConnection($port, ['timeout' => 1])->connect();
    }
}
