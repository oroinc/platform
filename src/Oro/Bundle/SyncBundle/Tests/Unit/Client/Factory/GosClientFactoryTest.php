<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Client\Factory;

use Gos\Component\WebSocketClient\Wamp\Client as GosClient;
use Oro\Bundle\SyncBundle\Client\Factory\GosClientFactory;

class GosClientFactoryTest extends \PHPUnit_Framework_TestCase
{
    private const WS_HOST = 'testHost';
    private const WS_PORT = 'testPort';
    private const WS_SECURED = true;
    private const WS_ORIGIN = 'testOrigin';

    public function testCreateGosClient()
    {
        $factory = new GosClientFactory();
        $gosClient = $factory->createGosClient(self::WS_HOST, self::WS_PORT, self::WS_SECURED, self::WS_ORIGIN);

        self::assertInstanceOf(GosClient::class, $gosClient);
    }
}
