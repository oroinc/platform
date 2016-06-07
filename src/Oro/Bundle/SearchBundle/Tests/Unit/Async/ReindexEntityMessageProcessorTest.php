<?php
namespace Oro\Bundle\SearchBundle\Tests\Unit\Async;

use Oro\Bundle\SearchBundle\Async\ReindexEntityMessageProcessor;
use Oro\Bundle\SearchBundle\Async\Topics;
use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Oro\Component\MessageQueue\Client\MessageProducer;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;

class ReindexEntityMessageProcessorTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new ReindexEntityMessageProcessor($this->createIndexerMock(), $this->createMessageProducerMock());
    }

    public function testShouldReturnSubscribedTopics()
    {
        $expectedSubscribedTopics = [Topics::REINDEX_ENTITIES];

        $this->assertEquals($expectedSubscribedTopics, ReindexEntityMessageProcessor::getSubscribedTopics());
    }

    public function testShouldReindexWholeIndexIfMessageIsEmpty()
    {
        $indexer = $this->createIndexerMock();
        $indexer
            ->expects($this->once())
            ->method('resetIndex')
        ;
        $indexer
            ->expects($this->once())
            ->method('getClassesForReindex')
            ->will($this->returnValue(['class-name']))
        ;

        $producer = $this->createMessageProducerMock();
        $producer
            ->expects($this->once())
            ->method('sendTo')
            ->with(Topics::INDEX_ENTITIES_BY_CLASS, 'class-name')
        ;

        $message = new NullMessage();

        $processor = new ReindexEntityMessageProcessor($indexer, $producer);
        $result = $processor->process($message, $this->getMock(SessionInterface::class));

        $this->assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testShouldReindexOnlySingleClass()
    {
        $indexer = $this->createIndexerMock();
        $indexer
            ->expects($this->once())
            ->method('resetIndex')
            ->with('class-name')
        ;
        $indexer
            ->expects($this->once())
            ->method('getClassesForReindex')
            ->with('class-name')
            ->will($this->returnValue(['class-name']))
        ;

        $producer = $this->createMessageProducerMock();
        $producer
            ->expects($this->once())
            ->method('sendTo')
            ->with(Topics::INDEX_ENTITIES_BY_CLASS, 'class-name')
        ;

        $message = new NullMessage();
        $message->setProperties([
            'json_body' => 'class-name',
        ]);

        $processor = new ReindexEntityMessageProcessor($indexer, $producer);
        $result = $processor->process($message, $this->getMock(SessionInterface::class));

        $this->assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testShouldReindexArrayOfClasses()
    {
        $indexer = $this->createIndexerMock();
        $indexer
            ->expects($this->once())
            ->method('resetIndex')
            ->with('class-name')
        ;
        $indexer
            ->expects($this->once())
            ->method('getClassesForReindex')
            ->with('class-name')
            ->will($this->returnValue(['class-name']))
        ;

        $producer = $this->createMessageProducerMock();
        $producer
            ->expects($this->once())
            ->method('sendTo')
            ->with(Topics::INDEX_ENTITIES_BY_CLASS, 'class-name')
        ;

        $message = new NullMessage();
        $message->setProperties([
            'json_body' => ['class-name'],
        ]);

        $processor = new ReindexEntityMessageProcessor($indexer, $producer);
        $result = $processor->process($message, $this->getMock(SessionInterface::class));

        $this->assertEquals(MessageProcessorInterface::ACK, $result);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|MessageProducer
     */
    public function createMessageProducerMock()
    {
        return $this->getMock(MessageProducer::class, [], [], '', false);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|IndexerInterface
     */
    public function createIndexerMock()
    {
        return $this->getMock(IndexerInterface::class);
    }
}
