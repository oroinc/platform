<?php

namespace Oro\Bundle\ReminderBundle\Tests\Unit\Model;

use Oro\Bundle\ReminderBundle\Entity\Reminder;
use Oro\Bundle\ReminderBundle\Model\ReminderSender;

class ReminderSenderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $registry;

    /**
     * @var ReminderSender
     */
    protected $sender;

    protected function setUp()
    {
        $this->registry = $this->getMockBuilder('Oro\\Bundle\\ReminderBundle\\Model\\SendProcessorRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->sender = new ReminderSender($this->registry);
    }

    public function testPush()
    {
        $method = 'foo_method';

        $reminder = $this->createMock('Oro\\Bundle\\ReminderBundle\\Entity\\Reminder');

        $reminder->expects($this->at(0))
            ->method('getMethod')
            ->will($this->returnValue($method));

        $processor = $this->createMock('Oro\\Bundle\\ReminderBundle\\Model\\SendProcessorInterface');

        $this->registry->expects($this->once())
            ->method('getProcessor')
            ->with($method)
            ->will($this->returnValue($processor));

        $processor->expects($this->once())
            ->method('push')
            ->with($reminder);

        $this->sender->push($reminder);
    }

    public function testSend()
    {
        $fooProcessor = $this->createMock('Oro\\Bundle\\ReminderBundle\\Model\\SendProcessorInterface');
        $barProcessor = $this->createMock('Oro\\Bundle\\ReminderBundle\\Model\\SendProcessorInterface');

        $this->registry->expects($this->once())
            ->method('getProcessors')
            ->will($this->returnValue(array($fooProcessor, $barProcessor)));

        $fooProcessor->expects($this->once())
            ->method('process');

        $barProcessor->expects($this->once())
            ->method('process');

        $this->sender->send();
    }
}
