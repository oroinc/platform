<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Processor\Shared\NormalizeEntityId;
use Oro\Bundle\ApiBundle\Processor\SingleItemContext;

class NormalizeEntityIdTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $valueNormalizer;

    /** @var NormalizeEntityId */
    protected $processor;

    protected function setUp()
    {
        $this->doctrineHelper  = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->valueNormalizer = $this->getMockBuilder('Oro\Bundle\ApiBundle\Request\ValueNormalizer')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new NormalizeEntityId($this->doctrineHelper, $this->valueNormalizer);
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

        $this->valueNormalizer->expects($this->at(0))
            ->method('normalizeValue')
            ->with('123', 'integer')
            ->willReturn(123);

        $this->processor->process($context);

        $this->assertSame(123, $context->getId());
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

        $this->valueNormalizer->expects($this->at(0))
            ->method('normalizeValue')
            ->with('123', 'integer')
            ->willReturn(123);
        $this->valueNormalizer->expects($this->at(1))
            ->method('normalizeValue')
            ->with('456', 'integer')
            ->willReturn(456);

        $this->processor->process($context);

        $this->assertSame(
            ['id1' => 123, 'id2' => 456],
            $context->getId()
        );
    }
}
