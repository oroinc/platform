<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Processor\Shared\NormalizeEntityId;
use Oro\Bundle\ApiBundle\Processor\SingleItemContext;

class NormalizeEntityIdTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var NormalizeEntityId */
    protected $processor;

    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new NormalizeEntityId($this->doctrineHelper);
    }

    public function testProcessWhenNoId()
    {
        $context = new SingleItemContext();

        $this->doctrineHelper->expects($this->never())
            ->method('getEntityMetadata');

        $this->processor->process($context);
    }

    public function testProcessWhenIdAlreadyNormalized()
    {
        $context = new SingleItemContext();
        $context->setId(123);

        $this->doctrineHelper->expects($this->never())
            ->method('getEntityMetadata');

        $this->processor->process($context);
    }

    public function testProcessWhenNoClass()
    {
        $context = new SingleItemContext();
        $context->setId('123');

        $this->doctrineHelper->expects($this->never())
            ->method('getEntityMetadata');

        $this->processor->process($context);
    }

    public function testProcessForNotManageableEntity()
    {
        $context = new SingleItemContext();
        $context->setClassName('Test\Class');
        $context->setId('123');

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntity')
            ->with('Test\Class')
            ->willReturn(false);

        $this->doctrineHelper->expects($this->never())
            ->method('getEntityMetadata');

        $this->processor->process($context);
    }

    public function testProcessForSingleIdentifier()
    {
        $entityClass = 'Test\Class';

        $context = new SingleItemContext();
        $context->setClassName($entityClass);
        $context->setId('123');

        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntity')
            ->with($entityClass)
            ->willReturn(true);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadata')
            ->with($entityClass)
            ->willReturn($metadata);

        $metadata->expects($this->once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $metadata->expects($this->once())
            ->method('getTypeOfField')
            ->with('id')
            ->willReturn('integer');

        $this->processor->process($context);

        $this->assertSame(123, $context->getId('123'));
    }

    public function testProcessForCompositeIdentifier()
    {
        $entityClass = 'Test\Class';

        $context = new SingleItemContext();
        $context->setClassName($entityClass);
        $context->setId('id1=123,id2=456');

        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntity')
            ->with($entityClass)
            ->willReturn(true);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadata')
            ->with($entityClass)
            ->willReturn($metadata);

        $metadata->expects($this->once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id1', 'id2']);
        $metadata->expects($this->exactly(2))
            ->method('getTypeOfField')
            ->willReturnMap(
                [
                    ['id1', 'integer'],
                    ['id2', 'integer'],
                ]
            );

        $this->processor->process($context);

        $this->assertSame(
            ['id1' => 123, 'id2' => 456],
            $context->getId('123')
        );
    }
}
