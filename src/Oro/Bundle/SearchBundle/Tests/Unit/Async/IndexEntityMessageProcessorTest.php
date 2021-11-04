<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Async;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\SearchBundle\Async\IndexEntityMessageProcessor;
use Oro\Bundle\SearchBundle\Async\Topics;
use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

class IndexEntityMessageProcessorTest extends \PHPUnit\Framework\TestCase
{
    public function testCouldBeConstructedWithRequiredAttributes()
    {
        new IndexEntityMessageProcessor(
            $this->createMock(IndexerInterface::class),
            $this->createMock(ManagerRegistry::class),
            $this->createMock(LoggerInterface::class)
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
        $indexer = $this->createMock(IndexerInterface::class);

        $doctrine = $this->createMock(ManagerRegistry::class);

        $message = new Message();
        $message->setBody(JSON::encode([
            'key' => 'value',
        ]));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with('Message is invalid. Class was not found.');

        $processor = new IndexEntityMessageProcessor($indexer, $doctrine, $logger);
        $result = $processor->process($message, $this->createMock(SessionInterface::class));

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldRejectMessageIfMessageHasNoId()
    {
        $indexer = $this->createMock(IndexerInterface::class);

        $doctrine = $this->createMock(ManagerRegistry::class);

        $message = new Message();
        $message->setBody(JSON::encode([
            'class' => 'class-name',
        ]));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with('Message is invalid. Id was not found.');

        $processor = new IndexEntityMessageProcessor($indexer, $doctrine, $logger);
        $result = $processor->process($message, $this->createMock(SessionInterface::class));

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldRejectMessageIfEntityMangerWasNotFoundForClass()
    {
        $indexer = $this->createMock(IndexerInterface::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->once())
            ->method('getManagerForClass');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with('Entity manager is not defined for class: "class-name"');

        $message = new Message();
        $message->setBody(JSON::encode([
            'class' => 'class-name',
            'id' => 'id',
        ]));

        $processor = new IndexEntityMessageProcessor($indexer, $doctrine, $logger);
        $result = $processor->process($message, $this->createMock(SessionInterface::class));

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldIndexEntityIfItExists()
    {
        $entity = new \stdClass();

        $indexer = $this->createMock(IndexerInterface::class);
        $indexer->expects($this->once())
            ->method('save')
            ->with($this->identicalTo($entity));

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects($this->once())
            ->method('find')
            ->with('id')
            ->willReturn($entity);

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects($this->once())
            ->method('getRepository')
            ->willReturn($repository);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with('class-name')
            ->willReturn($entityManager);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())
            ->method('error');

        $message = new Message();
        $message->setBody(JSON::encode([
            'class' => 'class-name',
            'id' => 'id',
        ]));

        $processor = new IndexEntityMessageProcessor($indexer, $doctrine, $logger);
        $result = $processor->process($message, $this->createMock(SessionInterface::class));

        $this->assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testShouldDeleteIndexForEntityIfEntityDoesNotExist()
    {
        $entity = new \stdClass();

        $indexer = $this->createMock(IndexerInterface::class);
        $indexer->expects($this->once())
            ->method('delete')
            ->with($this->identicalTo($entity));

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects($this->once())
            ->method('find')
            ->with('id')
            ->willReturn(null);

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects($this->once())
            ->method('getRepository')
            ->willReturn($repository);
        $entityManager->expects($this->once())
            ->method('getReference')
            ->with('class-name', 'id')
            ->willReturn($entity);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with('class-name')
            ->willReturn($entityManager);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())
            ->method('error');

        $message = new Message();
        $message->setBody(JSON::encode([
            'class' => 'class-name',
            'id' => 'id',
        ]));

        $processor = new IndexEntityMessageProcessor($indexer, $doctrine, $logger);
        $result = $processor->process($message, $this->createMock(SessionInterface::class));

        $this->assertEquals(MessageProcessorInterface::ACK, $result);
    }
}
