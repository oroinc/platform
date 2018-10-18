<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Client;

use Oro\Bundle\SyncBundle\Client\Wamp\Factory\ClientAttributes;

class ClientAttributesTest extends \PHPUnit\Framework\TestCase
{
    public function testGetters(): void
    {
        $host = 'sampleHost';
        $port = 8080;
        $path = '/samplePath';
        $transport = 'sampleTransport';
        $contextOptions = ['sampleKey' => 'sampleValue'];

        $clientAttributes = new ClientAttributes(
            $host,
            $port,
            '' . $path . '',
            $transport,
            $contextOptions
        );

        self::assertSame($host, $clientAttributes->getHost());
        self::assertSame($port, $clientAttributes->getPort());
        self::assertSame($path, $clientAttributes->getPath());
        self::assertSame($transport, $clientAttributes->getTransport());
        self::assertSame($contextOptions, $clientAttributes->getContextOptions());
    }
}
