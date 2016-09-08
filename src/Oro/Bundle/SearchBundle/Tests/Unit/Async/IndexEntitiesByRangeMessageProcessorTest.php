<?php
namespace Oro\Bundle\SearchBundle\Tests\Unit\Async;

use Oro\Bundle\SearchBundle\Async\IndexEntitiesByRangeMessageProcessor;
use Oro\Bundle\SearchBundle\Async\Topics;
use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;

class IndexEntitiesByRangeMessageProcessorTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithRequiredAttributes()
    {
        new IndexEntitiesByRangeMessageProcessor(
            $this->createDoctrineMock(),
            $this->createSearchIndexerMock(),
            $this->createJobRunnerMock(),
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
            ->with('Message is not valid: "{"offset":123,"limit":1000,"jobId":12345}"')
        ;

        $producer = $this->createSearchIndexerMock();

        $jobRunner = $this->createJobRunnerMock();
        $jobRunner
            ->expects($this->once())
            ->method('runDelayed')
            ->with(12345)
            ->will($this->returnCallback(function ($name, $callback) use ($jobRunner) {
                $callback($jobRunner);
            }))
        ;

        $message = new NullMessage();
        $message->setBody(JSON::encode([
            'offset' => 123,
            'limit' => 1000,
            'jobId' => 12345,
        ]));

        $processor = new IndexEntitiesByRangeMessageProcessor($doctrine, $producer, $jobRunner, $logger);
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
            ->with('Message is not valid: "{"entityClass":"entity-name","limit":6789,"jobId":12345}"')
        ;

        $producer = $this->createSearchIndexerMock();

        $jobRunner = $this->createJobRunnerMock();
        $jobRunner
            ->expects($this->once())
            ->method('runDelayed')
            ->with(12345)
            ->will($this->returnCallback(function ($name, $callback) use ($jobRunner) {
                $callback($jobRunner);
            }))
        ;

        $message = new NullMessage();
        $message->setBody(JSON::encode([
            'entityClass' => 'entity-name',
            'limit' => 6789,
            'jobId' => 12345,
        ]));

        $processor = new IndexEntitiesByRangeMessageProcessor($doctrine, $producer, $jobRunner, $logger);
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
            ->with('Message is not valid: "{"entityClass":"entity-name","offset":6789,"jobId":12345}"')
        ;

        $producer = $this->createSearchIndexerMock();

        $jobRunner = $this->createJobRunnerMock();
        $jobRunner
            ->expects($this->once())
            ->method('runDelayed')
            ->with(12345)
            ->will($this->returnCallback(function ($name, $callback) use ($jobRunner) {
                $callback($jobRunner);
            }))
        ;

        $message = new NullMessage();
        $message->setBody(JSON::encode([
            'entityClass' => 'entity-name',
            'offset' => 6789,
            'jobId' => 12345,
        ]));

        $processor = new IndexEntitiesByRangeMessageProcessor($doctrine, $producer, $jobRunner, $logger);
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

        $jobRunner = $this->createJobRunnerMock();
        $jobRunner
            ->expects($this->once())
            ->method('runDelayed')
            ->with(12345)
            ->will($this->returnCallback(function ($name, $callback) use ($jobRunner) {
                $callback($jobRunner);
            }))
        ;

        $message = new NullMessage();
        $message->setBody(json_encode([
            'entityClass' => 'entity-name',
            'offset' => 1235,
            'limit' => 6789,
            'jobId' => 12345,
        ]));

        $processor = new IndexEntitiesByRangeMessageProcessor($doctrine, $producer, $jobRunner, $logger);
        $result = $processor->process($message, $this->getMock(SessionInterface::class));

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|JobRunner
     */
    private function createJobRunnerMock()
    {
        return $this->getMock(JobRunner::class, [], [], '', false);
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
