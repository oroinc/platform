<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Client\Factory;

use Oro\Bundle\SyncBundle\Client\Wamp\Factory\ClientAttributes;
use Oro\Bundle\SyncBundle\Client\Wamp\Factory\WampClientFactory;
use Oro\Bundle\SyncBundle\Client\Wamp\WampClient;
use Psr\Log\LoggerInterface;

class WampClientFactoryTest extends \PHPUnit\Framework\TestCase
{
    public function testCreateClient(): void
    {
        $factory = new WampClientFactory();

        $logger = $this->createMock(LoggerInterface::class);
        $factory->setLogger($logger);

        $clientAttributes = $this->createMock(ClientAttributes::class);

        $host = 'example.org';
        $clientAttributes->expects(self::once())
            ->method('getHost')
            ->willReturn($host);

        $port = 8080;
        $clientAttributes->expects(self::once())
            ->method('getPort')
            ->willReturn($port);

        $transport = 'tls';
        $clientAttributes->expects(self::once())
            ->method('getTransport')
            ->willReturn($transport);

        $contextOptions = ['foo' => 'bar'];
        $clientAttributes->expects(self::once())
            ->method('getContextOptions')
            ->willReturn($contextOptions);

        $userAgent = 'user-agent/5.1.4';
        $clientAttributes->expects(self::once())
            ->method('getUserAgent')
            ->willReturn($userAgent);

        $wampClient = new WampClient(
            $host,
            $port,
            $transport,
            ['ssl' => $contextOptions],
            '127.0.0.1'
        );
        $wampClient->setUserAgent($userAgent);
        $wampClient->setLogger($logger);

        self::assertEquals($wampClient, $factory->createClient($clientAttributes));
    }
}
