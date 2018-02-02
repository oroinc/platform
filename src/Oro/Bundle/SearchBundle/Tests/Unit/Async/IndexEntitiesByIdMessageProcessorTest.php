<?php
namespace Oro\Bundle\SearchBundle\Tests\Unit\Async;

use Oro\Bundle\MessageQueueBundle\Test\Unit\MessageQueueExtension;
use Oro\Bundle\SearchBundle\Async\IndexEntitiesByIdMessageProcessor;
use Oro\Bundle\SearchBundle\Async\Topics;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;

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

        $result = $processor->process($message, $this->createMock(SessionInterface::class));

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
        self::assertMessagesEmpty(Topics::INDEX_ENTITY);
    }

    public function testShouldLogErrorIfNotEnoughDataToBuildJobName()
    {
        $message = new NullMessage();
        $message->setBody(json_encode(['class' => 'class-name']));

        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('error')
            ->with('Expected array with keys "class" and "context" but given: "class"')
        ;

        $processor = new IndexEntitiesByIdMessageProcessor(self::getMessageProducer(), $logger);
        $processor->setPropertyAccessor(new PropertyAccessor());

        $result = $processor->process($message, $this->createMock(SessionInterface::class));

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testBuildJobNameForMessage()
    {
        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->never())
            ->method('error')
        ;

        $jobRunner = $this->createMock(JobRunner::class);
        $jobRunner->expects($this->once())
            ->method('runUnique')
            ->willReturn(true)
            ->with('message id', 'search_reindex|d0d06767b38da968e7118c69f821bc1e');

        $processor = new IndexEntitiesByIdMessageProcessor(self::getMessageProducer(), $logger);
        $processor->setJobRunner($jobRunner);
        $processor->setPropertyAccessor(new PropertyAccessor());

        $message = new NullMessage();
        $message->setMessageId('message id');
        $message->setBody(json_encode(['class' => 'class-name', 'entityIds' => ['id']]));

        $result = $processor->process($message, $this->createMock(SessionInterface::class));

        $this->assertEquals(MessageProcessorInterface::ACK, $result);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|LoggerInterface
     */
    protected function createLoggerMock()
    {
        return $this->createMock(LoggerInterface::class);
    }
}
