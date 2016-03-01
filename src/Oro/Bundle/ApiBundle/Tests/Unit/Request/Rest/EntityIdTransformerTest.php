<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Request\Rest;

use Oro\Bundle\ApiBundle\Request\Rest\EntityIdTransformer;

class EntityIdTransformerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $valueNormalizer;

    /** @var EntityIdTransformer */
    protected $entityIdTransformer;

    protected function setUp()
    {
        $this->doctrineHelper  = $this->getMockBuilder('Oro\Bundle\ApiBundle\Util\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->valueNormalizer = $this->getMockBuilder('Oro\Bundle\ApiBundle\Request\ValueNormalizer')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityIdTransformer = new EntityIdTransformer($this->doctrineHelper, $this->valueNormalizer);
    }

    /**
     * @dataProvider transformProvider
     */
    public function testTransform($id, $expectedResult)
    {
        $result = $this->entityIdTransformer->transform($id);
        $this->assertSame($expectedResult, $result);
    }

    public function transformProvider()
    {
        return [
            [123, '123'],
            [['id1' => 123, 'id2' => 456], 'id1=123,id2=456'],
        ];
    }

    public function testReverseTransformForNotManageableEntity()
    {
        $entityClass = 'Test\Class';
        $value       = '123';

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with('Test\Class')
            ->willReturn(false);

        $this->doctrineHelper->expects($this->never())
            ->method('getEntityMetadataForClass');

        $result = $this->entityIdTransformer->reverseTransform($entityClass, $value);
        $this->assertSame($value, $result);
    }

    public function testReverseTransformForSingleIdentifier()
    {
        $entityClass = 'Test\Class';
        $value       = '123';

        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with($entityClass)
            ->willReturn(true);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadataForClass')
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

        $result = $this->entityIdTransformer->reverseTransform($entityClass, $value);
        $this->assertSame(123, $result);
    }

    public function testReverseTransformForCompositeIdentifier()
    {
        $entityClass = 'Test\Class';
        $value       = 'id1=123,id2=456';

        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with($entityClass)
            ->willReturn(true);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadataForClass')
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

        $result = $this->entityIdTransformer->reverseTransform($entityClass, $value);
        $this->assertSame(
            ['id1' => 123, 'id2' => 456],
            $result
        );
    }
}
