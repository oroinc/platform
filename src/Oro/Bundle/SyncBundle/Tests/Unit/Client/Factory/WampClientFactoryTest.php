<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Client\Factory;

use Oro\Bundle\SyncBundle\Client\Wamp\Factory\WampClientFactory;
use Oro\Bundle\SyncBundle\Provider\WebsocketClientParametersProviderInterface;

class WampClientFactoryTest extends \PHPUnit\Framework\TestCase
{
    public function testCreateClient(): void
    {
        $factory = new WampClientFactory();

        $clientParametersProvider = $this->createMock(WebsocketClientParametersProviderInterface::class);
        $clientParametersProvider->expects(self::once())
            ->method('getHost');
        $clientParametersProvider->expects(self::once())
            ->method('getPort');
        $clientParametersProvider->expects(self::once())
            ->method('getTransport');
        $clientParametersProvider->expects(self::once())
            ->method('getContextOptions');

        $factory->createClient($clientParametersProvider);
    }
}
