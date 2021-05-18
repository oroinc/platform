<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Consumption\Extension;

use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\Extension\LimitGarbageCollectionExtension;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageConsumerInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\Test\TestLogger;

class LimitGarbageCollectionExtensionTest extends \PHPUnit\Framework\TestCase
{
    private $gcEnabled;

    protected function setUp(): void
    {
        if (!function_exists('gc_status')) {
            $this->markTestSkipped('gc_status function required');
        }

        $this->gcEnabled = gc_enabled();

        gc_enable();
    }

    protected function tearDown(): void
    {
        $this->gcEnabled ? gc_enable() : gc_disable();
    }

    public function testCouldBeConstructedWithRequiredArguments()
    {
        new LimitGarbageCollectionExtension(12345);
    }

    public function testShouldThrowExceptionIfMessageLimitIsNotInt()
    {
        $this->expectException(\TypeError::class);
        new LimitGarbageCollectionExtension('test');
    }

    public function testInterruptWhenGarbageCollectionLimitReached()
    {
        $context = $this->createContext();

        $this->assertFalse($context->isExecutionInterrupted());

        $extension = new LimitGarbageCollectionExtension(5);

        $objects = [];
        for ($i = 1; $i < 10; $i++) {
            gc_collect_cycles();
        }

        $extension->onBeforeReceive($context);
        $this->assertTrue($context->isExecutionInterrupted());

        $this->assertTrue(
            $context->getLogger()->hasDebug(
                'Message consumption is interrupted since the GC runs limit reached. limit: "5"'
            )
        );
    }

    protected function createContext(): Context
    {
        $context = new Context($this->createMock(SessionInterface::class));
        $context->setLogger(new TestLogger());
        $context->setMessageConsumer($this->createMock(MessageConsumerInterface::class));
        $context->setMessageProcessor($this->createMock(MessageProcessorInterface::class));

        return $context;
    }
}
