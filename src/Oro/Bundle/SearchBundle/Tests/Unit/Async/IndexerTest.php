<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Async;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\MessageQueueBundle\Test\Unit\MessageQueueExtension;
use Oro\Bundle\SearchBundle\Async\Indexer;
use Oro\Bundle\SearchBundle\Async\Topic\IndexEntitiesByIdTopic;
use Oro\Bundle\SearchBundle\Async\Topic\ReindexTopic;
use Oro\Bundle\SearchBundle\Transformer\MessageTransformer;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class IndexerTest extends TestCase
{
    use MessageQueueExtension;

    public function testCouldBeConstructedWithRequiredArguments(): void
    {
        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        new Indexer(
            $this->createMock(MessageProducerInterface::class),
            $doctrineHelper,
            new MessageTransformer($doctrineHelper)
        );
    }

    public function testResetIndexShouldThrowExceptionMethodIsNotImplemented(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Method is not implemented');

        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $indexer = new Indexer(
            self::getMessageProducer(),
            $doctrineHelper,
            new MessageTransformer($doctrineHelper)
        );

        $indexer->resetIndex();
    }

    public function testGetClassesForReindexShouldThrowExceptionMethodIsNotImplemented(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Method is not implemented');

        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $indexer = new Indexer(
            self::getMessageProducer(),
            $doctrineHelper,
            new MessageTransformer($doctrineHelper)
        );

        $indexer->getClassesForReindex();
    }

    public function testSaveShouldReturnFalseIfEntityIsNull(): void
    {
        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $doctrineHelper->expects($this->never())
            ->method($this->anything());

        $indexer = new Indexer(
            self::getMessageProducer(),
            $doctrineHelper,
            new MessageTransformer($doctrineHelper)
        );
        $result = $indexer->save(null);

        $this->assertFalse($result);
        self::assertMessagesEmpty(IndexEntitiesByIdTopic::getName());
    }

    public function testDeleteShouldReturnFalseIfEntityIsNull(): void
    {
        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $doctrineHelper->expects($this->never())
            ->method($this->anything());

        $indexer = new Indexer(
            self::getMessageProducer(),
            $doctrineHelper,
            new MessageTransformer($doctrineHelper)
        );
        $result = $indexer->delete(null);

        $this->assertFalse($result);
        self::assertMessagesEmpty(IndexEntitiesByIdTopic::getName());
    }

    public function testSaveShouldAcceptSingleEntityAndSendMessageToProducer(): void
    {
        $entity = new \stdClass();

        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($this->identicalTo($entity))
            ->willReturn(35);
        $doctrineHelper->expects($this->once())
            ->method('getEntityClass')
            ->with($this->identicalTo($entity))
            ->willReturn('entity-name');

        $indexer = new Indexer(
            self::getMessageProducer(),
            $doctrineHelper,
            new MessageTransformer($doctrineHelper)
        );
        $result = $indexer->save($entity);

        $this->assertTrue($result);
        self::assertMessageSent(
            IndexEntitiesByIdTopic::getName(),
            ['class' => 'entity-name', 'entityIds' => [35 => 35]]
        );
    }

    public function testSaveShouldAcceptArrayOfEntitiesAndSendMessageToProducer(): void
    {
        $entities = [new \stdClass()];

        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($this->identicalTo($entities[0]))
            ->willReturn(35);
        $doctrineHelper->expects($this->once())
            ->method('getEntityClass')
            ->with($this->identicalTo($entities[0]))
            ->willReturn('entity-name');

        $indexer = new Indexer(
            self::getMessageProducer(),
            $doctrineHelper,
            new MessageTransformer($doctrineHelper)
        );
        $result = $indexer->save($entities);

        $this->assertTrue($result);
        self::assertMessageSent(
            IndexEntitiesByIdTopic::getName(),
            ['class' => 'entity-name', 'entityIds' => [35 => 35]]
        );
    }

    public function testDeleteShouldAcceptSingleEntityAndSendMessageToProducer(): void
    {
        $entity = new \stdClass();

        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($this->identicalTo($entity))
            ->willReturn(35);
        $doctrineHelper->expects($this->once())
            ->method('getEntityClass')
            ->with($this->identicalTo($entity))
            ->willReturn('entity-name');

        $indexer = new Indexer(
            self::getMessageProducer(),
            $doctrineHelper,
            new MessageTransformer($doctrineHelper)
        );
        $result = $indexer->delete($entity);

        $this->assertTrue($result);
        self::assertMessageSent(
            IndexEntitiesByIdTopic::getName(),
            ['class' => 'entity-name', 'entityIds' => [35 => 35]]
        );
    }

    public function testDeleteShouldAcceptArrayOfEntitiesAndSendMessageToProducer(): void
    {
        $entities = [new \stdClass()];

        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($this->identicalTo($entities[0]))
            ->willReturn(35);
        $doctrineHelper->expects($this->once())
            ->method('getEntityClass')
            ->with($this->identicalTo($entities[0]))
            ->willReturn('entity-name');

        $indexer = new Indexer(
            self::getMessageProducer(),
            $doctrineHelper,
            new MessageTransformer($doctrineHelper)
        );
        $result = $indexer->delete($entities);

        $this->assertTrue($result);
        self::assertMessageSent(
            IndexEntitiesByIdTopic::getName(),
            ['class' => 'entity-name', 'entityIds' => [35 => 35]]
        );
    }

    public function testReindexShouldAcceptSingleEntityClassAndSendMessageToProducer(): void
    {
        $class = 'class-name';

        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $indexer = new Indexer(
            self::getMessageProducer(),
            $doctrineHelper,
            new MessageTransformer($doctrineHelper)
        );
        $indexer->reindex($class);

        self::assertMessageSent(ReindexTopic::getName(), ['class-name']);
    }

    public function testReindexShouldAcceptArrayOfEntityClassesAndSendMessageToProducer(): void
    {
        $classes = ['class-name'];

        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $indexer = new Indexer(
            self::getMessageProducer(),
            $doctrineHelper,
            new MessageTransformer($doctrineHelper)
        );
        $indexer->reindex($classes);

        self::assertMessageSent(ReindexTopic::getName(), ['class-name']);
    }

    public function testReindexShouldAcceptNullAndSendMessageToProducer(): void
    {
        $classes = null;

        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $indexer = new Indexer(
            self::getMessageProducer(),
            $doctrineHelper,
            new MessageTransformer($doctrineHelper)
        );
        $indexer->reindex($classes);

        self::assertMessageSent(ReindexTopic::getName(), []);
    }

    public function testReindexShouldNotAcceptInvalidEntity(): void
    {
        $this->expectException(\ReflectionException::class);
        $entities = [new \stdClass()];

        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $doctrineHelper->expects($this->once())
            ->method('getEntityManagerForClass')
            ->with($this->identicalTo($entities[0]))
            ->willThrowException(new \ReflectionException());

        $indexer = new Indexer(
            self::getMessageProducer(),
            $doctrineHelper,
            new MessageTransformer($doctrineHelper)
        );
        $indexer->reindex($entities);
    }
}
