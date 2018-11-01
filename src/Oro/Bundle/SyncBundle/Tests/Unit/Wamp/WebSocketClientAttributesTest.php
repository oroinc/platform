<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Wamp;

use Oro\Bundle\SyncBundle\Wamp\WebSocketClientAttributes;

class WebSocketClientAttributesTest extends \PHPUnit_Framework_TestCase
{
    private const TEST_CONTEXT_OPTIONS = ['test', 'context', 'options'];
    private const TEST_TRANSPORT = 'test-transport';
    private const TEST_PATH = 'test-path';
    private const TEST_PORT = 123;
    private const TEST_HOST = 'test-host';

    public function testAddQueryParameter(): void
    {
        $attributes = new WebSocketClientAttributes(
            self::TEST_HOST,
            self::TEST_PORT,
            self::TEST_PATH,
            self::TEST_TRANSPORT,
            self::TEST_CONTEXT_OPTIONS
        );

        self::assertSame(self::TEST_HOST, $attributes->getHost());
        self::assertSame(self::TEST_PORT, $attributes->getPort());
        self::assertSame(self::TEST_PATH, $attributes->getPath());
        self::assertSame(self::TEST_TRANSPORT, $attributes->getTransport());
        self::assertSame(self::TEST_CONTEXT_OPTIONS, $attributes->getContextOptions());
    }
}
