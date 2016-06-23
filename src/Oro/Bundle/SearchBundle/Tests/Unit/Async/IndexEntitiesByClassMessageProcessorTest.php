<?php
namespace Oro\Bundle\SearchBundle\Tests\Unit\Async;

use Doctrine\ORM\Query;
use Oro\Bundle\SearchBundle\Async\IndexEntitiesByClassMessageProcessor;
use Oro\Bundle\SearchBundle\Async\Topics;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;

class IndexEntitiesByClassMessageProcessorTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithRequiredAttributes()
    {
        new IndexEntitiesByClassMessageProcessor(
            $this->createDoctrineMock(),
            $this->createMessageProducerMock(),
            $this->createLoggerMock()
        );
    }

    public function testShouldBeSubscribedForTopics()
    {
        $expectedSubscribedTopics = [
            Topics::INDEX_ENTITY_TYPE,
        ];

        $this->assertEquals($expectedSubscribedTopics, IndexEntitiesByClassMessageProcessor::getSubscribedTopics());
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

        $producer = $this->createMessageProducerMock();

        $message = new NullMessage();
        $message->setBody('entity-name');

        $processor = new IndexEntitiesByClassMessageProcessor($doctrine, $producer, $logger);
        $result = $processor->process($message, $this->getMock(SessionInterface::class));

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|MessageProducerInterface
     */
    protected function createMessageProducerMock()
    {
        return $this->getMock(MessageProducerInterface::class, [], [], '', false);
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
