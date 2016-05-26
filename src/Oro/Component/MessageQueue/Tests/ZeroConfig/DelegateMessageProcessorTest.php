<?php
namespace Oro\Component\MessageQueue\Tests\ZeroConfig;

use Oro\Component\MessageQueue\Consumption\MessageProcessor;
use Oro\Component\MessageQueue\Transport\Amqp\AmqpMessage;
use Oro\Component\MessageQueue\Transport\Session;
use Oro\Component\MessageQueue\ZeroConfig\Config;
use Oro\Component\MessageQueue\ZeroConfig\MessageProcessorRegistryInterface;
use Oro\Component\MessageQueue\ZeroConfig\DelegateMessageProcessor;

class DelegateMessageProcessorTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new DelegateMessageProcessor($this->createMessageProcessorRegistryMock());
    }

    public function testShouldThrowExceptionIfProcessorNameIsNotSet()
    {
        $this->setExpectedException(
            \LogicException::class,
            'Got message without required parameter: "oro.message_queue.zero_config.processor_name"'
        );

        $processor = new DelegateMessageProcessor($this->createMessageProcessorRegistryMock());
        $processor->process(new AmqpMessage(), $this->createTransportSessionMock());
    }

    public function testShouldProcessMessage()
    {
        $session = $this->createTransportSessionMock();
        $message = new AmqpMessage();
        $message->setProperties([
            Config::PARAMETER_PROCESSOR_NAME => 'processor-name',
        ]);

        $messageProcessor = $this->createMessageProcessorMock();
        $messageProcessor
            ->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($message), $this->identicalTo($session))
            ->will($this->returnValue('return-value'))
        ;

        $processorRegistry = $this->createMessageProcessorRegistryMock();
        $processorRegistry
            ->expects($this->once())
            ->method('get')
            ->with('processor-name')
            ->will($this->returnValue($messageProcessor))
        ;

        $processor = new DelegateMessageProcessor($processorRegistry);
        $return = $processor->process($message, $session);

        $this->assertEquals('return-value', $return);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|MessageProcessorRegistryInterface
     */
    protected function createMessageProcessorRegistryMock()
    {
        return $this->getMock(MessageProcessorRegistryInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Session
     */
    protected function createTransportSessionMock()
    {
        return $this->getMock(Session::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|MessageProcessor
     */
    protected function createMessageProcessorMock()
    {
        return $this->getMock(MessageProcessor::class);
    }
}
