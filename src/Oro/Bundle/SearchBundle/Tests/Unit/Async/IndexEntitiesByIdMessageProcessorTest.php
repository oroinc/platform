<?php
namespace Oro\Bundle\SearchBundle\Tests\Unit\Async;

use Oro\Bundle\MessageQueueBundle\Test\Unit\MessageQueueExtension;
use Oro\Bundle\SearchBundle\Async\IndexEntitiesByIdMessageProcessor;
use Oro\Bundle\SearchBundle\Async\Topics;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

class IndexEntitiesByIdMessageProcessorTest extends \PHPUnit_Framework_TestCase
{
    use MessageQueueExtension;

    public function testCouldBeConstructedByRequiredArguments()
    {
        new IndexEntitiesByIdMessageProcessor(
            self::getMessageProducer(),
            $this->createLoggerMock()
        );
    }

    public function testShouldReturnSubscribedTopics()
    {
        $expectedSubscribedTopics = [Topics::INDEX_ENTITIES];

        $this->assertEquals($expectedSubscribedTopics, IndexEntitiesByIdMessageProcessor::getSubscribedTopics());
    }

    public function testShouldRejectMessageIfIsNotArray()
    {
        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('error')
            ->with('Expected array but got: "NULL"')
        ;

        $processor = new IndexEntitiesByIdMessageProcessor(self::getMessageProducer(), $logger);

        $message = new NullMessage();
        $message->setBody('');

        $result = $processor->process($message, $this->getMock(SessionInterface::class));

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
        self::assertMessagesEmpty(Topics::INDEX_ENTITY);
    }

    public function testShouldLogErrorIfClassWasNotFound()
    {
        $message = new NullMessage();
        $message->setBody(json_encode(
            [[]]
        ));

        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('error')
            ->with('Message is invalid. Class was not found.', ['entity' => []])
        ;

        $processor = new IndexEntitiesByIdMessageProcessor(self::getMessageProducer(), $logger);

        $result = $processor->process($message, $this->getMock(SessionInterface::class));

        $this->assertEquals(MessageProcessorInterface::ACK, $result);
        self::assertMessagesEmpty(Topics::INDEX_ENTITY);
    }

    public function testShouldLogErrorIfIdWasNotFound()
    {
        $message = new NullMessage();
        $message->setBody(json_encode(
            [
                [
                    'class' => 'class-name',
                ],
            ]
        ));

        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('error')
            ->with('Message is invalid. Id was not found.', ['entity' => ['class' => 'class-name']])
        ;

        $processor = new IndexEntitiesByIdMessageProcessor(self::getMessageProducer(), $logger);

        $result = $processor->process($message, $this->getMock(SessionInterface::class));

        $this->assertEquals(MessageProcessorInterface::ACK, $result);
        self::assertMessagesEmpty(Topics::INDEX_ENTITY);
    }

    public function testShouldPublishMessageToProducer()
    {
        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->never())
            ->method('error')
        ;

        $processor = new IndexEntitiesByIdMessageProcessor(self::getMessageProducer(), $logger);

        $message = new NullMessage();
        $message->setBody(json_encode(
            [
                [
                    'class' => 'class-name',
                    'id' => 'id',
                ],
            ]
        ));

        $result = $processor->process($message, $this->getMock(SessionInterface::class));

        $this->assertEquals(MessageProcessorInterface::ACK, $result);
        self::assertMessageSent(
            Topics::INDEX_ENTITY,
            ['class' => 'class-name', 'id' => 'id']
        );
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|LoggerInterface
     */
    protected function createLoggerMock()
    {
        return $this->getMock(LoggerInterface::class);
    }
}
