<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Create;

use Oro\Bundle\ApiBundle\Processor\Create\SetEntityId;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;

class SetEntityIdTest extends FormProcessorTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var SetEntityId */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->doctrineHelper = $this
            ->getMockBuilder('Oro\Bundle\ApiBundle\Util\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new SetEntityId($this->doctrineHelper);
    }

    public function testProcessWhenEntityIdDoesNotExistInContext()
    {
        $this->doctrineHelper->expects($this->never())
            ->method('setEntityIdentifier');

        $this->context->setResult(new \stdClass());
        $this->processor->process($this->context);
    }

    public function testProcessWhenEntityDoesNotExistInContext()
    {
        $this->doctrineHelper->expects($this->never())
            ->method('setEntityIdentifier');

        $this->context->setId(123);
        $this->processor->process($this->context);
    }

    public function testProcessForUnsupportedEntity()
    {
        $this->doctrineHelper->expects($this->never())
            ->method('setEntityIdentifier');

        $this->context->setId(123);
        $this->context->setResult([]);
        $this->processor->process($this->context);
    }

    public function testProcessForNotManageableEntity()
    {
        $entity = new \stdClass();

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadataForClass')
            ->with(get_class($entity), false)
            ->willReturn(null);
        $this->doctrineHelper->expects($this->never())
            ->method('setEntityIdentifier');

        $this->context->setId(123);
        $this->context->setClassName(get_class($entity));
        $this->context->setResult($entity);
        $this->processor->process($this->context);
    }

    public function testProcessForManageableEntityUsesIdGenerator()
    {
        $entity = new \stdClass();

        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->expects($this->once())
            ->method('usesIdGenerator')
            ->willReturn(true);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadataForClass')
            ->with(get_class($entity), false)
            ->willReturn($metadata);
        $this->doctrineHelper->expects($this->never())
            ->method('setEntityIdentifier');

        $this->context->setId(123);
        $this->context->setClassName(get_class($entity));
        $this->context->setResult($entity);
        $this->processor->process($this->context);
    }

    public function testProcessForManageableEntityWithoutIdGenerator()
    {
        $entityId = 123;
        $entity = new \stdClass();

        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->expects($this->once())
            ->method('usesIdGenerator')
            ->willReturn(false);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadataForClass')
            ->with(get_class($entity), false)
            ->willReturn($metadata);
        $this->doctrineHelper->expects($this->once())
            ->method('setEntityIdentifier')
            ->with($this->identicalTo($entity), $entityId, $this->identicalTo($metadata));

        $this->context->setId($entityId);
        $this->context->setClassName(get_class($entity));
        $this->context->setResult($entity);
        $this->processor->process($this->context);
    }
}
