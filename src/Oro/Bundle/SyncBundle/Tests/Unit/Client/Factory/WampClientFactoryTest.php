<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Client\Factory;

use Oro\Bundle\SyncBundle\Client\Wamp\Factory\ClientAttributes;
use Oro\Bundle\SyncBundle\Client\Wamp\Factory\WampClientFactory;

class WampClientFactoryTest extends \PHPUnit\Framework\TestCase
{
    public function testCreateClient(): void
    {
        $factory = new WampClientFactory();

        /** @var ClientAttributes|\PHPUnit\Framework\MockObject\MockObject $clientAttributes */
        $clientAttributes = $this->createMock(ClientAttributes::class);

        $clientAttributes
            ->expects(self::once())
            ->method('getHost');

        $clientAttributes
            ->expects(self::once())
            ->method('getPort');

        $clientAttributes
            ->expects(self::once())
            ->method('getTransport');

        $clientAttributes
            ->expects(self::once())
            ->method('getContextOptions');

        $factory->createClient($clientAttributes);
    }
}
