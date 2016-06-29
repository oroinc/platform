<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Create;

use Oro\Bundle\ApiBundle\Processor\Create\SaveEntity;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;

class SaveEntityTest extends FormProcessorTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var SaveEntity */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\ApiBundle\Util\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new SaveEntity($this->doctrineHelper);
    }

    public function testProcessWhenNoEntity()
    {
        $this->doctrineHelper->expects($this->never())
            ->method('getEntityManager');

        $this->processor->process($this->context);
    }

    public function testProcessForNotSupportedEntity()
    {
        $this->doctrineHelper->expects($this->never())
            ->method('getEntityManager');

        $this->context->setResult([]);
        $this->processor->process($this->context);
    }

    public function testProcessForNotManageableEntity()
    {
        $entity = new \stdClass();

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManager')
            ->with($this->identicalTo($entity), false)
            ->willReturn(null);

        $this->context->setResult($entity);
        $this->processor->process($this->context);
    }

    public function testProcessForManageableEntityWithSingleId()
    {
        $entity = new \stdClass();
        $entityId = 123;

        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManager')
            ->with($this->identicalTo($entity), false)
            ->willReturn($em);
        $em->expects($this->once())
            ->method('getClassMetadata')
            ->with(get_class($entity))
            ->willReturn($metadata);
        $metadata->expects($this->once())
            ->method('getIdentifierValues')
            ->with($this->identicalTo($entity))
            ->willReturn(['id' => $entityId]);

        $em->expects($this->once())
            ->method('persist')
            ->with($this->identicalTo($entity));
        $em->expects($this->once())
            ->method('flush')
            ->with($this->identicalTo($entity));

        $this->context->setResult($entity);
        $this->processor->process($this->context);

        $this->assertEquals($entityId, $this->context->getId());
    }

    public function testProcessForManageableEntityWithCompositeId()
    {
        $entity = new \stdClass();
        $entityId = ['id1' => 1, 'id2' => 2];

        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManager')
            ->with($this->identicalTo($entity), false)
            ->willReturn($em);
        $em->expects($this->once())
            ->method('getClassMetadata')
            ->with(get_class($entity))
            ->willReturn($metadata);
        $metadata->expects($this->once())
            ->method('getIdentifierValues')
            ->with($this->identicalTo($entity))
            ->willReturn($entityId);

        $em->expects($this->once())
            ->method('persist')
            ->with($this->identicalTo($entity));
        $em->expects($this->once())
            ->method('flush')
            ->with($this->identicalTo($entity));

        $this->context->setResult($entity);
        $this->processor->process($this->context);

        $this->assertEquals($entityId, $this->context->getId());
    }

    public function testProcessForManageableEntityWhenIdWasNotGenerated()
    {
        $entity = new \stdClass();

        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManager')
            ->with($this->identicalTo($entity), false)
            ->willReturn($em);
        $em->expects($this->once())
            ->method('getClassMetadata')
            ->with(get_class($entity))
            ->willReturn($metadata);
        $metadata->expects($this->once())
            ->method('getIdentifierValues')
            ->with($this->identicalTo($entity))
            ->willReturn([]);

        $em->expects($this->once())
            ->method('persist')
            ->with($this->identicalTo($entity));
        $em->expects($this->once())
            ->method('flush')
            ->with($this->identicalTo($entity));

        $this->context->setResult($entity);
        $this->processor->process($this->context);

        $this->assertNull($this->context->getId());
    }
}
