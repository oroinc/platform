<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Client;

use Oro\Component\MessageQueue\Client\MessageProcessorRegistry;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Consumption\NullMessageProcessor;

class MessageProcessorRegistryTest extends \PHPUnit\Framework\TestCase
{
    protected function getServiceLocator(array $factories): MessageProcessorRegistry
    {
        return new MessageProcessorRegistry($factories);
    }

    public function testHas(): void
    {
        $locator = $this->getServiceLocator(
            [
                'foo' => static fn () => $this->createMock(MessageProcessorInterface::class),
            ]
        );

        self::assertTrue($locator->has('foo'));
        self::assertFalse($locator->has('bar'));
    }

    public function testGet(): void
    {
        $messageProcessor = $this->createMock(MessageProcessorInterface::class);
        $locator = $this->getServiceLocator(
            [
                'foo' => static fn () => $messageProcessor,
            ]
        );

        self::assertSame($messageProcessor, $locator->get('foo'));
        self::assertEquals(new NullMessageProcessor('bar'), $locator->get('bar'));
    }
}
