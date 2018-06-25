<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Async;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\MessageQueueBundle\Test\Unit\MessageQueueExtension;
use Oro\Bundle\SearchBundle\Async\Indexer;
use Oro\Bundle\SearchBundle\Async\Topics;
use Oro\Bundle\SearchBundle\Transformer\MessageTransformer;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class IndexerTest extends \PHPUnit\Framework\TestCase
{
    use MessageQueueExtension;

    public function testCouldBeConstructedWithRequiredArguments()
    {
        $doctrineHelper = $this->createDoctrineHelperMock();
        new Indexer(
            $this->createMock(MessageProducerInterface::class),
            $doctrineHelper,
            new MessageTransformer($doctrineHelper)
        );
    }

    public function testResetIndexShouldThrowExceptionMethodIsNotImplemented()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Method is not implemented');

        $doctrineHelper = $this->createDoctrineHelperMock();
        $indexer = new Indexer(
            self::getMessageProducer(),
            $doctrineHelper,
            new MessageTransformer($doctrineHelper)
        );

        $indexer->resetIndex();
    }

    public function testGetClassesForReindexShouldThrowExceptionMethodIsNotImplemented()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Method is not implemented');

        $doctrineHelper = $this->createDoctrineHelperMock();
        $indexer = new Indexer(
            self::getMessageProducer(),
            $doctrineHelper,
            new MessageTransformer($doctrineHelper)
        );

        $indexer->getClassesForReindex();
    }


    public function testSaveShouldReturnFalseIfEntityIsNull()
    {
        $doctrineHelper = $this->createDoctrineHelperMock();
        $doctrineHelper
            ->expects($this->never())
            ->method('getEntityIdentifier')
        ;

        $indexer = new Indexer(
            self::getMessageProducer(),
            $doctrineHelper,
            new MessageTransformer($doctrineHelper)
        );
        $result = $indexer->save(null);

        $this->assertFalse($result);
        self::assertMessagesEmpty(Topics::INDEX_ENTITIES);
    }

    public function testDeleteShouldReturnFalseIfEntityIsNull()
    {
        $doctrineHelper = $this->createDoctrineHelperMock();
        $doctrineHelper
            ->expects($this->never())
            ->method('getEntityIdentifier')
        ;

        $indexer = new Indexer(
            self::getMessageProducer(),
            $doctrineHelper,
            new MessageTransformer($doctrineHelper)
        );
        $result = $indexer->delete(null);

        $this->assertFalse($result);
        self::assertMessagesEmpty(Topics::INDEX_ENTITIES);
    }

    public function testSaveShouldAcceptSingleEntityAndSendMessageToProducer()
    {
        $entity = new \stdClass();

        $doctrineHelper = $this->createDoctrineHelperMock();
        $doctrineHelper
            ->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($this->identicalTo($entity))
            ->will($this->returnValue(35))
        ;
        $doctrineHelper
            ->expects($this->once())
            ->method('getEntityClass')
            ->with($this->identicalTo($entity))
            ->will($this->returnValue('entity-name'))
        ;

        $indexer = new Indexer(
            self::getMessageProducer(),
            $doctrineHelper,
            new MessageTransformer($doctrineHelper)
        );
        $result = $indexer->save($entity);

        $this->assertTrue($result);
        self::assertMessageSent(
            Topics::INDEX_ENTITIES,
            ['class' => 'entity-name', 'entityIds' => [35 => 35]]
        );
    }

    public function testSaveShouldAcceptArrayOfEntitiesAndSendMessageToProducer()
    {
        $entities = [new \stdClass()];

        $doctrineHelper = $this->createDoctrineHelperMock();
        $doctrineHelper
            ->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($this->identicalTo($entities[0]))
            ->will($this->returnValue(35))
        ;
        $doctrineHelper
            ->expects($this->once())
            ->method('getEntityClass')
            ->with($this->identicalTo($entities[0]))
            ->will($this->returnValue('entity-name'))
        ;

        $indexer = new Indexer(
            self::getMessageProducer(),
            $doctrineHelper,
            new MessageTransformer($doctrineHelper)
        );
        $result = $indexer->save($entities);

        $this->assertTrue($result);
        self::assertMessageSent(
            Topics::INDEX_ENTITIES,
            ['class' => 'entity-name', 'entityIds' => [35 => 35]]
        );
    }

    public function testDeleteShouldAcceptSingleEntityAndSendMessageToProducer()
    {
        $entity = new \stdClass();

        $doctrineHelper = $this->createDoctrineHelperMock();
        $doctrineHelper
            ->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($this->identicalTo($entity))
            ->will($this->returnValue(35))
        ;
        $doctrineHelper
            ->expects($this->once())
            ->method('getEntityClass')
            ->with($this->identicalTo($entity))
            ->will($this->returnValue('entity-name'))
        ;

        $indexer = new Indexer(
            self::getMessageProducer(),
            $doctrineHelper,
            new MessageTransformer($doctrineHelper)
        );
        $result = $indexer->delete($entity);

        $this->assertTrue($result);
        self::assertMessageSent(
            Topics::INDEX_ENTITIES,
            ['class' => 'entity-name', 'entityIds' => [35 => 35]]
        );
    }

    public function testDeleteShouldAcceptArrayOfEntitiesAndSendMessageToProducer()
    {
        $entities = [new \stdClass()];

        $doctrineHelper = $this->createDoctrineHelperMock();
        $doctrineHelper
            ->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($this->identicalTo($entities[0]))
            ->will($this->returnValue(35))
        ;
        $doctrineHelper
            ->expects($this->once())
            ->method('getEntityClass')
            ->with($this->identicalTo($entities[0]))
            ->will($this->returnValue('entity-name'))
        ;


        $indexer = new Indexer(
            self::getMessageProducer(),
            $doctrineHelper,
            new MessageTransformer($doctrineHelper)
        );
        $result = $indexer->delete($entities);

        $this->assertTrue($result);
        self::assertMessageSent(
            Topics::INDEX_ENTITIES,
            ['class' => 'entity-name', 'entityIds' => [35 => 35]]
        );
    }

    public function testReindexShouldAcceptSingleEntityClassAndSendMessageToProducer()
    {
        $class = 'class-name';

        $doctrineHelper = $this->createDoctrineHelperMock();
        $indexer = new Indexer(
            self::getMessageProducer(),
            $doctrineHelper,
            new MessageTransformer($doctrineHelper)
        );
        $indexer->reindex($class);

        self::assertMessageSent(Topics::REINDEX, ['class-name']);
    }

    public function testReindexShouldAcceptArrayOfEntityClassesAndSendMessageToProducer()
    {
        $classes = ['class-name'];

        $doctrineHelper = $this->createDoctrineHelperMock();
        $indexer = new Indexer(
            self::getMessageProducer(),
            $doctrineHelper,
            new MessageTransformer($doctrineHelper)
        );
        $indexer->reindex($classes);

        self::assertMessageSent(Topics::REINDEX, ['class-name']);
    }

    public function testReindexShouldAcceptNullAndSendMessageToProducer()
    {
        $classes = null;

        $doctrineHelper = $this->createDoctrineHelperMock();
        $indexer = new Indexer(
            self::getMessageProducer(),
            $doctrineHelper,
            new MessageTransformer($doctrineHelper)
        );
        $indexer->reindex($classes);

        self::assertMessageSent(Topics::REINDEX, []);
    }

    /**
     * @expectedException \ReflectionException
     */
    public function testReindexShouldNotAcceptInvalidEntity()
    {
        $entities = [new \stdClass()];

        $doctrineHelper = $this->createDoctrineHelperMock();
        $doctrineHelper
            ->expects($this->once())
            ->method('getEntityManagerForClass')
            ->with($this->identicalTo($entities[0]))
            ->will($this->throwException(new \ReflectionException()))
        ;

        $indexer = new Indexer(
            self::getMessageProducer(),
            $doctrineHelper,
            new MessageTransformer($doctrineHelper)
        );
        $indexer->reindex($entities);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper
     */
    protected function createDoctrineHelperMock()
    {
        return $this->createMock(DoctrineHelper::class);
    }
}
