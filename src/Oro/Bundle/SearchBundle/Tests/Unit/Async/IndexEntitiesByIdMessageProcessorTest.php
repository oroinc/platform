<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Async;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\MessageQueueBundle\Test\Unit\MessageQueueExtension;
use Oro\Bundle\SearchBundle\Async\IndexEntitiesByIdMessageProcessor;
use Oro\Bundle\SearchBundle\Async\Topics;
use Oro\Bundle\SearchBundle\Engine\AbstractIndexer;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

class IndexEntitiesByIdMessageProcessorTest extends \PHPUnit_Framework_TestCase
{
    use MessageQueueExtension;

    /** @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $logger;

    /** @var JobRunner|\PHPUnit_Framework_MockObject_MockObject */
    private $jobRunner;

    /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject */
    private $doctrineHelper;

    /** @var AbstractIndexer|\PHPUnit_Framework_MockObject_MockObject */
    private $indexer;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->jobRunner = $this->createMock(JobRunner::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->indexer = $this->createMock(AbstractIndexer::class);

        $this->processor = new IndexEntitiesByIdMessageProcessor(
            $this->jobRunner,
            $this->doctrineHelper,
            $this->indexer
        );
        $this->processor->setLogger($this->logger);
    }

    public function testShouldReturnSubscribedTopics()
    {
        $expectedSubscribedTopics = [Topics::INDEX_ENTITIES];

        $this->assertEquals($expectedSubscribedTopics, IndexEntitiesByIdMessageProcessor::getSubscribedTopics());
    }

    public function testShouldRejectMessageIfIsNotArray()
    {
        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with('Expected array but got: "NULL"')
        ;

        $message = new NullMessage();
        $message->setBody('');

        $result = $this->processor->process($message, $this->createMock(SessionInterface::class));

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
        self::assertMessagesEmpty(Topics::INDEX_ENTITY);
    }

    public function testShouldLogErrorIfNotEnoughDataToBuildJobName()
    {
        $message = new NullMessage();
        $message->setBody(json_encode(['class' => 'class-name']));

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with('Expected array with keys "class" and "context" but given: "class"')
        ;

        $result = $this->processor->process($message, $this->createMock(SessionInterface::class));

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testBuildJobNameForMessage()
    {
        $this->logger
            ->expects($this->never())
            ->method('error')
        ;

        $this->jobRunner
            ->expects($this->once())
            ->method('runUnique')
            ->willReturn(true)
            ->with('message id', 'search_reindex|d0d06767b38da968e7118c69f821bc1e')
        ;

        $message = new NullMessage();
        $message->setMessageId('message id');
        $message->setBody(json_encode(['class' => 'class-name', 'entityIds' => ['id']]));

        $result = $this->processor->process($message, $this->createMock(SessionInterface::class));

        $this->assertEquals(MessageProcessorInterface::ACK, $result);
    }
}
