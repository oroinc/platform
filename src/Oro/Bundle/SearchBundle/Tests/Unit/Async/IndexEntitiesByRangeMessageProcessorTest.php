<?php
namespace Oro\Bundle\SearchBundle\Tests\Unit\Async;

use Doctrine\ORM\Query;
use Oro\Bundle\SearchBundle\Async\IndexEntitiesByRangeMessageProcessor;
use Oro\Bundle\SearchBundle\Async\Topics;
use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;

class IndexEntitiesByRangeMessageProcessorTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithRequiredAttributes()
    {
        new IndexEntitiesByRangeMessageProcessor(
            $this->createDoctrineMock(),
            $this->createSearchIndexerMock(),
            $this->createLoggerMock()
        );
    }

    public function testShouldBeSubscribedForTopics()
    {
        $expectedSubscribedTopics = [
            Topics::INDEX_ENTITY_BY_RANGE,
        ];

        $this->assertEquals($expectedSubscribedTopics, IndexEntitiesByRangeMessageProcessor::getSubscribedTopics());
    }

    public function testShouldRejectMessageIfClassIsNotSetInMessage()
    {
        $doctrine = $this->createDoctrineMock();

        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('error')
            ->with('Message is not valid: ""')
        ;

        $producer = $this->createSearchIndexerMock();

        $message = new NullMessage();
        $message->setBody('');

        $processor = new IndexEntitiesByRangeMessageProcessor($doctrine, $producer, $logger);
        $result = $processor->process($message, $this->getMock(SessionInterface::class));

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldRejectMessageIfOffsetIsNotSetInMessage()
    {
        $doctrine = $this->createDoctrineMock();

        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('error')
            ->with('Message is not valid: "{"class":"entity-name","limit":6789}"')
        ;

        $producer = $this->createSearchIndexerMock();

        $message = new NullMessage();
        $message->setBody(json_encode([
            'class' => 'entity-name',
            'limit' => 6789,
        ]));

        $processor = new IndexEntitiesByRangeMessageProcessor($doctrine, $producer, $logger);
        $result = $processor->process($message, $this->getMock(SessionInterface::class));

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldRejectMessageIfLimitIsNotSetInMessage()
    {
        $doctrine = $this->createDoctrineMock();

        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('error')
            ->with('Message is not valid: "{"class":"entity-name","offset":6789}"')
        ;

        $producer = $this->createSearchIndexerMock();

        $message = new NullMessage();
        $message->setBody(json_encode([
            'class' => 'entity-name',
            'offset' => 6789,
        ]));

        $processor = new IndexEntitiesByRangeMessageProcessor($doctrine, $producer, $logger);
        $result = $processor->process($message, $this->getMock(SessionInterface::class));

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldRejectMessageIfEntityManagerWasNotFoundForClass()
    {
        $doctrine = $this->createDoctrineMock();
        $doctrine
            ->expects($this->once())
            ->method('getManagerForClass')
        ;

        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('error')
            ->with('Entity manager is not defined for class: "entity-name"')
        ;

        $producer = $this->createSearchIndexerMock();

        $message = new NullMessage();
        $message->setBody(json_encode([
            'class' => 'entity-name',
            'offset' => 1235,
            'limit' => 6789,
        ]));

        $processor = new IndexEntitiesByRangeMessageProcessor($doctrine, $producer, $logger);
        $result = $processor->process($message, $this->getMock(SessionInterface::class));

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|IndexerInterface
     */
    protected function createSearchIndexerMock()
    {
        return $this->getMock(IndexerInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|RegistryInterface
     */
    protected function createDoctrineMock()
    {
        return $this->getMock(RegistryInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|LoggerInterface
     */
    protected function createLoggerMock()
    {
        return $this->getMock(LoggerInterface::class);
    }
}
