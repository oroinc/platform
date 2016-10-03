<?php
namespace Oro\Bundle\SearchBundle\Tests\Unit\Async;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\SearchBundle\Async\IndexEntityMessageProcessor;
use Oro\Bundle\SearchBundle\Async\Topics;
use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;

class IndexEntityMessageProcessorTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithRequiredAttributes()
    {
        new IndexEntityMessageProcessor(
            $this->createIndexerMock(),
            $this->createDoctrineMock(),
            $this->createLoggerMock()
        );
    }

    public function testShouldReturnSubscribedTopics()
    {
        $expectedSubscribedTopics = [
            Topics::INDEX_ENTITY,
        ];

        $this->assertEquals($expectedSubscribedTopics, IndexEntityMessageProcessor::getSubscribedTopics());
    }

    public function testShouldRejectMessageIfMessageHasNoClass()
    {
        $indexer = $this->createIndexerMock();

        $doctrine = $this->createDoctrineMock();

        $message = new NullMessage();
        $message->setBody(json_encode(
            [
                'key' => 'value',
            ]
        ));

        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('error')
            ->with(
                'Message is invalid. Class was not found.',
                [
                    'message' => $message
                ]
            )
        ;

        $processor = new IndexEntityMessageProcessor($indexer, $doctrine, $logger);
        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldRejectMessageIfMessageHasNoId()
    {
        $indexer = $this->createIndexerMock();

        $doctrine = $this->createDoctrineMock();

        $message = new NullMessage();
        $message->setBody(json_encode(
            [
                'class' => 'class-name',
            ]
        ));

        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('error')
            ->with(
                'Message is invalid. Id was not found.',
                ['message' => $message]
            )
        ;

        $processor = new IndexEntityMessageProcessor($indexer, $doctrine, $logger);
        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldRejectMessageIfEntityMangerWasNotFoundForClass()
    {
        $indexer = $this->createIndexerMock();

        $doctrine = $this->createDoctrineMock();
        $doctrine
            ->expects($this->once())
            ->method('getManagerForClass')
        ;

        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('error')
            ->with('Entity manager is not defined for class: "class-name"')
        ;

        $message = new NullMessage();
        $message->setBody(json_encode(
            [
                'class' => 'class-name',
                'id' => 'id',
            ]
        ));

        $processor = new IndexEntityMessageProcessor($indexer, $doctrine, $logger);
        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldIndexEntityIfItExists()
    {
        $entity = new \stdClass();

        $indexer = $this->createIndexerMock();
        $indexer
            ->expects($this->once())
            ->method('save')
            ->with($this->identicalTo($entity))
        ;

        $repository = $this->createEntityRepositoryMock();
        $repository
            ->expects($this->once())
            ->method('find')
            ->with('id')
            ->will($this->returnValue($entity))
        ;

        $entityManager = $this->createEntityManagerMock();
        $entityManager
            ->expects($this->once())
            ->method('getRepository')
            ->will($this->returnValue($repository))
        ;

        $doctrine = $this->createDoctrineMock();
        $doctrine
            ->expects($this->once())
            ->method('getManagerForClass')
            ->with('class-name')
            ->will($this->returnValue($entityManager))
        ;

        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->never())
            ->method('error')
        ;

        $message = new NullMessage();
        $message->setBody(json_encode(
            [
                'class' => 'class-name',
                'id' => 'id',
            ]
        ));

        $processor = new IndexEntityMessageProcessor($indexer, $doctrine, $logger);
        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testShouldDeleteIndexForEntityIfEntityDoesNotExist()
    {
        $entity = new \stdClass();

        $indexer = $this->createIndexerMock();
        $indexer
            ->expects($this->once())
            ->method('delete')
            ->with($this->identicalTo($entity))
        ;

        $repository = $this->createEntityRepositoryMock();
        $repository
            ->expects($this->once())
            ->method('find')
            ->with('id')
            ->will($this->returnValue(null))
        ;

        $entityManager = $this->createEntityManagerMock();
        $entityManager
            ->expects($this->once())
            ->method('getRepository')
            ->will($this->returnValue($repository))
        ;
        $entityManager
            ->expects($this->once())
            ->method('getReference')
            ->with('class-name', 'id')
            ->will($this->returnValue($entity))
        ;

        $doctrine = $this->createDoctrineMock();
        $doctrine
            ->expects($this->once())
            ->method('getManagerForClass')
            ->with('class-name')
            ->will($this->returnValue($entityManager))
        ;

        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->never())
            ->method('error')
        ;

        $message = new NullMessage();
        $message->setBody(json_encode(
            [
                'class' => 'class-name',
                'id' => 'id',
            ]
        ));

        $processor = new IndexEntityMessageProcessor($indexer, $doctrine, $logger);
        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(MessageProcessorInterface::ACK, $result);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|EntityManager
     */
    protected function createEntityRepositoryMock()
    {
        return $this->getMock(EntityRepository::class, [], [], '', false);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|EntityManager
     */
    protected function createEntityManagerMock()
    {
        return $this->getMock(EntityManager::class, [], [], '', false);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|SessionInterface
     */
    protected function createSessionMock()
    {
        return $this->getMock(SessionInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|IndexerInterface
     */
    protected function createIndexerMock()
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
