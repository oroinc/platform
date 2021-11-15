<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Consumption\Extension;

use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\Extension\LimitObjectExtension;
use Oro\Component\MessageQueue\Transport\MessageConsumerInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\Test\TestLogger;

class LimitObjectExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new LimitObjectExtension(12345);
    }

    public function testShouldThrowExceptionIfMessageLimitIsNotInt()
    {
        $this->expectException(\TypeError::class);
        new LimitObjectExtension('test');
    }

    public function testInterruptWhenObjectLimitReached()
    {
        $context = $this->createContext();

        $this->assertFalse($context->isExecutionInterrupted());

        $extension = new LimitObjectExtension(1);

        $extension->onBeforeReceive($context);
        $this->assertTrue($context->isExecutionInterrupted());

        $this->assertTrue(
            $context->getLogger()->hasDebug(
                'Message consumption is interrupted since the object limit reached. limit: "1"'
            )
        );
    }

    public function testInterruptWhenObjectLimitReachedWithMultipleObjects()
    {
        $context = $this->createContext();

        $this->assertFalse($context->isExecutionInterrupted());

        $objectLimit = spl_object_id(new \stdClass()) + 100;

        /** GC reduces $objectLimit during runtime, so number of created objects should be way greater that limit */
        $amountOfObjectsToCreate = 100;

        $collection = new \ArrayObject();

        for ($i = 0; $i < $objectLimit * $amountOfObjectsToCreate; $i++) {
            $object = new \stdClass();
            $collection->append($object);
        }

        $extension = new LimitObjectExtension($objectLimit);

        $extension->onBeforeReceive($context);
        $this->assertTrue($context->isExecutionInterrupted());

        $this->assertTrue(
            $context->getLogger()->hasDebug(
                sprintf('Message consumption is interrupted since the object limit reached. limit: "%s"', $objectLimit)
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
