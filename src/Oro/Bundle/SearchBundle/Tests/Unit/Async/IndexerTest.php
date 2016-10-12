<?php
namespace Oro\Bundle\SearchBundle\Tests\Unit\Async;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SearchBundle\Async\Indexer;
use Oro\Bundle\SearchBundle\Async\Topics;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

class IndexerTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new Indexer($this->createMessageProducerMock(), $this->createDoctrineHelperMock());
    }

    public function testResetIndexShouldThrowExceptionMethodIsNotImplemented()
    {
        $this->setExpectedException(\LogicException::class, 'Method is not implemented');

        $indexer = new Indexer($this->createMessageProducerMock(), $this->createDoctrineHelperMock());

        $indexer->resetIndex();
    }

    public function testGetClassesForReindexShouldThrowExceptionMethodIsNotImplemented()
    {
        $this->setExpectedException(\LogicException::class, 'Method is not implemented');

        $indexer = new Indexer($this->createMessageProducerMock(), $this->createDoctrineHelperMock());

        $indexer->getClassesForReindex();
    }


    public function testSaveShouldReturnFalseIfEntityIsNull()
    {
        $producer = $this->createMessageProducerMock();
        $producer
            ->expects($this->never())
            ->method('send')
        ;

        $doctrineHelper = $this->createDoctrineHelperMock();
        $doctrineHelper
            ->expects($this->never())
            ->method('getEntityIdentifier')
        ;

        $indexer = new Indexer($producer, $doctrineHelper);
        $result = $indexer->save(null);

        $this->assertFalse($result);
    }

    public function testDeleteShouldReturnFalseIfEntityIsNull()
    {
        $producer = $this->createMessageProducerMock();
        $producer
            ->expects($this->never())
            ->method('send')
        ;

        $doctrineHelper = $this->createDoctrineHelperMock();
        $doctrineHelper
            ->expects($this->never())
            ->method('getEntityIdentifier')
        ;

        $indexer = new Indexer($producer, $doctrineHelper);
        $result = $indexer->delete(null);

        $this->assertFalse($result);
    }

    public function testSaveShouldAcceptSingleEntityAndSendMessageToProducer()
    {
        $entity = new \stdClass();

        $expectedMessage = [
            [
                'class' => 'entity-name',
                'id' => 'identity'
            ]
        ];

        $producer = $this->createMessageProducerMock();
        $producer
            ->expects($this->once())
            ->method('send')
            ->with(Topics::INDEX_ENTITIES, $this->identicalTo($expectedMessage))
        ;

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

        $indexer = new Indexer($producer, $doctrineHelper);
        $result = $indexer->save($entity);

        $this->assertTrue($result);
    }

    public function testSaveShouldAcceptArrayOfEntitiesAndSendMessageToProducer()
    {
        $entities = [new \stdClass()];

        $expectedMessage = [
            [
                'class' => 'entity-name',
                'id' => 'identity'
            ]
        ];

        $producer = $this->createMessageProducerMock();
        $producer
            ->expects($this->once())
            ->method('send')
            ->with(Topics::INDEX_ENTITIES, $this->identicalTo($expectedMessage))
        ;

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

        $indexer = new Indexer($producer, $doctrineHelper);
        $result = $indexer->save($entities);

        $this->assertTrue($result);
    }

    public function testDeleteShouldAcceptSingleEntityAndSendMessageToProducer()
    {
        $entity = new \stdClass();

        $expectedMessage = [
            [
                'class' => 'entity-name',
                'id' => 'identity'
            ]
        ];

        $producer = $this->createMessageProducerMock();
        $producer
            ->expects($this->once())
            ->method('send')
            ->with(Topics::INDEX_ENTITIES, $this->identicalTo($expectedMessage))
        ;

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

        $indexer = new Indexer($producer, $doctrineHelper);
        $result = $indexer->delete($entity);

        $this->assertTrue($result);
    }

    public function testDeleteShouldAcceptArrayOfEntitiesAndSendMessageToProducer()
    {
        $entities = [new \stdClass()];

        $expectedMessage = [
            [
                'class' => 'entity-name',
                'id' => 'identity'
            ]
        ];

        $producer = $this->createMessageProducerMock();
        $producer
            ->expects($this->once())
            ->method('send')
            ->with(Topics::INDEX_ENTITIES, $this->identicalTo($expectedMessage))
        ;

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


        $indexer = new Indexer($producer, $doctrineHelper);
        $result = $indexer->delete($entities);

        $this->assertTrue($result);
    }

    public function testReindexShouldAcceptSingleEntityClassAndSendMessageToProducer()
    {
        $class = 'class-name';

        $expectedMessage = ['class-name'];

        $producer = $this->createMessageProducerMock();
        $producer
            ->expects($this->once())
            ->method('send')
            ->with(Topics::REINDEX, $this->identicalTo($expectedMessage))
        ;

        $indexer = new Indexer($producer, $this->createDoctrineHelperMock());
        $indexer->reindex($class);
    }

    public function testReindexShouldAcceptArrayOfEntityClassesAndSendMessageToProducer()
    {
        $classes = ['class-name'];

        $expectedMessage = ['class-name'];

        $producer = $this->createMessageProducerMock();
        $producer
            ->expects($this->once())
            ->method('send')
            ->with(Topics::REINDEX, $this->identicalTo($expectedMessage))
        ;

        $indexer = new Indexer($producer, $this->createDoctrineHelperMock());
        $indexer->reindex($classes);
    }

    public function testReindexShouldAcceptNullAndSendMessageToProducer()
    {
        $classes = null;

        $expectedMessage = [];

        $producer = $this->createMessageProducerMock();
        $producer
            ->expects($this->once())
            ->method('send')
            ->with(Topics::REINDEX, $this->identicalTo($expectedMessage))
        ;

        $indexer = new Indexer($producer, $this->createDoctrineHelperMock());
        $indexer->reindex($classes);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|MessageProducerInterface
     */
    protected function createMessageProducerMock()
    {
        return $this->getMock(MessageProducerInterface::class, [], [], '', false);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper
     */
    protected function createDoctrineHelperMock()
    {
        return $this->getMock(DoctrineHelper::class, [], [], '', false);
    }
}
