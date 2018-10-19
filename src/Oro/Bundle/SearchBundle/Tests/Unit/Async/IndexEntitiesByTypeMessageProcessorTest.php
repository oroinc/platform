<?php
namespace Oro\Bundle\SearchBundle\Tests\Unit\Async;

use Oro\Bundle\MessageQueueBundle\Test\Unit\MessageQueueExtension;
use Oro\Bundle\SearchBundle\Async\IndexEntitiesByTypeMessageProcessor;
use Oro\Bundle\SearchBundle\Async\Topics;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;

class IndexEntitiesByTypeMessageProcessorTest extends \PHPUnit\Framework\TestCase
{
    use MessageQueueExtension;

    public function testCouldBeConstructedWithRequiredAttributes()
    {
        new IndexEntitiesByTypeMessageProcessor(
            $this->createDoctrineMock(),
            $this->createJobRunnerMock(),
            $this->createMock(MessageProducerInterface::class),
            $this->createLoggerMock()
        );
    }

    public function testShouldBeSubscribedForTopics()
    {
        $expectedSubscribedTopics = [
            Topics::INDEX_ENTITY_TYPE,
        ];

        $this->assertEquals($expectedSubscribedTopics, IndexEntitiesByTypeMessageProcessor::getSubscribedTopics());
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
            'jobId' => 12345,
        ]));

        $processor = new IndexEntitiesByTypeMessageProcessor(
            $doctrine,
            $jobRunner,
            self::getMessageProducer(),
            $logger
        );
        $result = $processor->process($message, $this->createMock(SessionInterface::class));

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|JobRunner
     */
    private function createJobRunnerMock()
    {
        return $this->createMock(JobRunner::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|RegistryInterface
     */
    protected function createDoctrineMock()
    {
        return $this->createMock(RegistryInterface::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|LoggerInterface
     */
    protected function createLoggerMock()
    {
        return $this->createMock(LoggerInterface::class);
    }
}
