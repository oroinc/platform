<?php

namespace Oro\Bundle\ReminderBundle\Tests\Unit\Model;

use Oro\Bundle\ReminderBundle\Entity\Reminder;
use Oro\Bundle\ReminderBundle\Model\ReminderSender;
use Oro\Bundle\ReminderBundle\Model\SendProcessorInterface;
use Oro\Bundle\ReminderBundle\Model\SendProcessorRegistry;

class ReminderSenderTest extends \PHPUnit\Framework\TestCase
{
    /** @var SendProcessorRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var ReminderSender */
    private $sender;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(SendProcessorRegistry::class);

        $this->sender = new ReminderSender($this->registry);
    }

    public function testPush()
    {
        $method = 'foo_method';

        $reminder = $this->createMock(Reminder::class);
        $reminder->expects($this->once())
            ->method('getMethod')
            ->willReturn($method);

        $processor = $this->createMock(SendProcessorInterface::class);
        $this->registry->expects($this->once())
            ->method('getProcessor')
            ->with($method)
            ->willReturn($processor);
        $processor->expects($this->once())
            ->method('push')
            ->with($reminder);

        $this->sender->push($reminder);
    }

    public function testSend()
    {
        $fooProcessor = $this->createMock(SendProcessorInterface::class);
        $barProcessor = $this->createMock(SendProcessorInterface::class);

        $this->registry->expects($this->once())
            ->method('getProcessors')
            ->willReturn([$fooProcessor, $barProcessor]);

        $fooProcessor->expects($this->once())
            ->method('process');

        $barProcessor->expects($this->once())
            ->method('process');

        $this->sender->send();
    }
}
