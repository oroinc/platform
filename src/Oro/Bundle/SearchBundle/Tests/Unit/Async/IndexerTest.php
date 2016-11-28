<?php
namespace Oro\Bundle\SearchBundle\Tests\Unit\Async;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\MessageQueueBundle\Test\Unit\MessageQueueExtension;
use Oro\Bundle\SearchBundle\Async\Indexer;
use Oro\Bundle\SearchBundle\Async\Topics;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

class IndexerTest extends \PHPUnit_Framework_TestCase
{
    use MessageQueueExtension;

    public function testCouldBeConstructedWithRequiredArguments()
    {
        new Indexer($this->getMock(MessageProducerInterface::class), $this->createDoctrineHelperMock());
    }

    public function testResetIndexShouldThrowExceptionMethodIsNotImplemented()
    {
        $this->setExpectedException(\LogicException::class, 'Method is not implemented');

        $indexer = new Indexer(self::getMessageProducer(), $this->createDoctrineHelperMock());

        $indexer->resetIndex();
    }

    public function testGetClassesForReindexShouldThrowExceptionMethodIsNotImplemented()
    {
        $this->setExpectedException(\LogicException::class, 'Method is not implemented');

        $indexer = new Indexer(self::getMessageProducer(), $this->createDoctrineHelperMock());

        $indexer->getClassesForReindex();
    }


    public function testSaveShouldReturnFalseIfEntityIsNull()
    {
        $doctrineHelper = $this->createDoctrineHelperMock();
        $doctrineHelper
            ->expects($this->never())
            ->method('getEntityIdentifier')
        ;

        $indexer = new Indexer(self::getMessageProducer(), $doctrineHelper);
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

        $indexer = new Indexer(self::getMessageProducer(), $doctrineHelper);
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
            ->will($this->returnValue('identity'))
        ;
        $doctrineHelper
            ->expects($this->once())
            ->method('getEntityMetadata')
            ->with($this->identicalTo($entity))
            ->will($this->returnValue(new ClassMetadata('entity-name')))
        ;

        $indexer = new Indexer(self::getMessageProducer(), $doctrineHelper);
        $result = $indexer->save($entity);

        $this->assertTrue($result);
        self::assertMessageSent(
            Topics::INDEX_ENTITIES,
            [
                ['class' => 'entity-name', 'id' => 'identity']
            ]
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
            ->will($this->returnValue('identity'))
        ;
        $doctrineHelper
            ->expects($this->once())
            ->method('getEntityMetadata')
            ->with($this->identicalTo($entities[0]))
            ->will($this->returnValue(new ClassMetadata('entity-name')))
        ;

        $indexer = new Indexer(self::getMessageProducer(), $doctrineHelper);
        $result = $indexer->save($entities);

        $this->assertTrue($result);
        self::assertMessageSent(
            Topics::INDEX_ENTITIES,
            [
                ['class' => 'entity-name', 'id' => 'identity']
            ]
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
            ->will($this->returnValue('identity'))
        ;
        $doctrineHelper
            ->expects($this->once())
            ->method('getEntityMetadata')
            ->with($this->identicalTo($entity))
            ->will($this->returnValue(new ClassMetadata('entity-name')))
        ;

        $indexer = new Indexer(self::getMessageProducer(), $doctrineHelper);
        $result = $indexer->delete($entity);

        $this->assertTrue($result);
        self::assertMessageSent(
            Topics::INDEX_ENTITIES,
            [
                ['class' => 'entity-name', 'id' => 'identity']
            ]
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
            ->will($this->returnValue('identity'))
        ;
        $doctrineHelper
            ->expects($this->once())
            ->method('getEntityMetadata')
            ->with($this->identicalTo($entities[0]))
            ->will($this->returnValue(new ClassMetadata('entity-name')))
        ;


        $indexer = new Indexer(self::getMessageProducer(), $doctrineHelper);
        $result = $indexer->delete($entities);

        $this->assertTrue($result);
        self::assertMessageSent(
            Topics::INDEX_ENTITIES,
            [
                ['class' => 'entity-name', 'id' => 'identity']
            ]
        );
    }

    public function testReindexShouldAcceptSingleEntityClassAndSendMessageToProducer()
    {
        $class = 'class-name';

        $indexer = new Indexer(self::getMessageProducer(), $this->createDoctrineHelperMock());
        $indexer->reindex($class);

        self::assertMessageSent(Topics::REINDEX, ['class-name']);
    }

    public function testReindexShouldAcceptArrayOfEntityClassesAndSendMessageToProducer()
    {
        $classes = ['class-name'];

        $indexer = new Indexer(self::getMessageProducer(), $this->createDoctrineHelperMock());
        $indexer->reindex($classes);

        self::assertMessageSent(Topics::REINDEX, ['class-name']);
    }

    public function testReindexShouldAcceptNullAndSendMessageToProducer()
    {
        $classes = null;

        $indexer = new Indexer(self::getMessageProducer(), $this->createDoctrineHelperMock());
        $indexer->reindex($classes);

        self::assertMessageSent(Topics::REINDEX, []);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper
     */
    protected function createDoctrineHelperMock()
    {
        return $this->getMock(DoctrineHelper::class, [], [], '', false);
    }
}
