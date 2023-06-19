<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Client\Factory;

use Oro\Bundle\SyncBundle\Client\Wamp\Factory\WampClientFactory;
use Oro\Bundle\SyncBundle\Client\Wamp\WampClient;
use Oro\Bundle\SyncBundle\Provider\WebsocketClientParametersProviderInterface;
use Psr\Log\LoggerInterface;

class WampClientFactoryTest extends \PHPUnit\Framework\TestCase
{
    public function testCreateClient(): void
    {
        $factory = new WampClientFactory();

        $logger = $this->createMock(LoggerInterface::class);
        $factory->setLogger($logger);

        $clientParametersProvider = $this->createMock(WebsocketClientParametersProviderInterface::class);

        $host = 'example.org';
        $clientParametersProvider->expects(self::once())
            ->method('getHost')
            ->willReturn($host);

        $port = 8080;
        $clientParametersProvider->expects(self::once())
            ->method('getPort')
            ->willReturn($port);

        $transport = 'tls';
        $clientParametersProvider->expects(self::once())
            ->method('getTransport')
            ->willReturn($transport);

        $contextOptions = ['foo' => 'bar'];
        $clientParametersProvider->expects(self::once())
            ->method('getContextOptions')
            ->willReturn($contextOptions);

        $userAgent = 'user-agent/5.1.4';
        $clientParametersProvider->expects(self::once())
            ->method('getUserAgent')
            ->willReturn($userAgent);

        $wampClient = new WampClient(
            $host,
            $port,
            $transport,
            ['ssl' => $contextOptions],
            '127.0.0.1',
            $userAgent
        );
        $wampClient->setLogger($logger);

        self::assertEquals($wampClient, $factory->createClient($clientParametersProvider));
    }
}
