<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Consumption\Extension;

use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\Extension\LimitGarbageCollectionExtension;
use Oro\Component\MessageQueue\Transport\MessageConsumerInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\Test\TestLogger;

class LimitGarbageCollectionExtensionTest extends \PHPUnit\Framework\TestCase
{
    private $gcEnabled;

    protected function setUp(): void
    {
        if (!function_exists('gc_status')) {
            self::markTestSkipped('gc_status function required');
        }

        $this->gcEnabled = gc_enabled();

        gc_enable();
    }

    protected function tearDown(): void
    {
        $this->gcEnabled ? gc_enable() : gc_disable();
    }

    public function testCouldBeConstructedWithRequiredArguments(): void
    {
        new LimitGarbageCollectionExtension(12345);
    }

    public function testShouldThrowExceptionIfMessageLimitIsNotInt(): void
    {
        $this->expectException(\TypeError::class);
        new LimitGarbageCollectionExtension('test');
    }

    public function testInterruptWhenGarbageCollectionLimitReached(): void
    {
        $context = $this->createContext();

        self::assertFalse($context->isExecutionInterrupted());

        // This code makes garbage collector do 3 cycles.
        $a = new \stdClass();
        $a->b = $a->c = $a->d = [];
        for ($i = 0; $i < 10000; $i++) {
            $b = new \stdClass();
            $a->b[] = $b;
            $c = new \stdClass();
            $a->c[] = $c;
            $d = new \stdClass();
            $a->d[] = $d;
        }

        gc_collect_cycles();

        $extension = new LimitGarbageCollectionExtension(2);
        $extension->onBeforeReceive($context);

        self::assertTrue($context->isExecutionInterrupted());

        self::assertTrue(
            $context->getLogger()->hasDebug(
                'Message consumption is interrupted since the GC runs limit reached. limit: "2"'
            )
        );
    }

    protected function createContext(): Context
    {
        $context = new Context($this->createMock(SessionInterface::class));
        $context->setLogger(new TestLogger());
        $context->setMessageConsumer($this->createMock(MessageConsumerInterface::class));
        $context->setMessageProcessorName('sample_processor');

        return $context;
    }
}
