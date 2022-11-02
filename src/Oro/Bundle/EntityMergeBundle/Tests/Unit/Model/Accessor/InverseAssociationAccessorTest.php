<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Model\Accessor;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\EntityMergeBundle\Doctrine\DoctrineHelper;
use Oro\Bundle\EntityMergeBundle\Metadata\DoctrineMetadata;
use Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata;
use Oro\Bundle\EntityMergeBundle\Model\Accessor\InverseAssociationAccessor;
use Oro\Bundle\EntityMergeBundle\Tests\Unit\Stub\CollectionItemStub;
use Oro\Bundle\EntityMergeBundle\Tests\Unit\Stub\EntityStub;

class InverseAssociationAccessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var InverseAssociationAccessor */
    private $accessor;

    protected function setUp(): void
    {
        $repository = $this->createMock(ObjectRepository::class);
        $repository->expects($this->any())
            ->method('findBy')
            ->willReturn([]);

        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->willReturn($repository);

        $this->accessor = new InverseAssociationAccessor($doctrineHelper);
    }

    public function testGetName()
    {
        $this->assertEquals('inverse_association', $this->accessor->getName());
    }

    public function testSupportsFalseWhenDefinedBySourceEntity()
    {
        $entity = new EntityStub();
        $fieldMetadata = $this->createMock(FieldMetadata::class);

        $fieldMetadata->expects($this->once())
            ->method('isDefinedBySourceEntity')
            ->willReturn(true);

        $this->assertFalse($this->accessor->supports($entity, $fieldMetadata));
    }

    public function testSupportsFalseWhenNotHasDoctrineMetadata()
    {
        $entity = new EntityStub();
        $fieldMetadata = $this->createMock(FieldMetadata::class);

        $fieldMetadata->expects($this->once())
            ->method('isDefinedBySourceEntity')
            ->willReturn(false);

        $fieldMetadata->expects($this->once())
            ->method('hasDoctrineMetadata')
            ->willReturn(false);

        $this->assertFalse($this->accessor->supports($entity, $fieldMetadata));
    }

    public function testSupportsTrue()
    {
        $entity = new EntityStub();

        $doctrineMetadata = $this->createMock(DoctrineMetadata::class);
        $doctrineMetadata->expects($this->once())
            ->method('isManyToOne')
            ->willReturn(false);
        $doctrineMetadata->expects($this->once())
            ->method('isOneToOne')
            ->willReturn(true);

        $fieldMetadata = $this->createMock(FieldMetadata::class);
        $fieldMetadata->expects($this->once())
            ->method('isDefinedBySourceEntity')
            ->willReturn(false);
        $fieldMetadata->expects($this->once())
            ->method('hasDoctrineMetadata')
            ->willReturn(true);
        $fieldMetadata->expects($this->once())
            ->method('getDoctrineMetadata')
            ->willReturn($doctrineMetadata);

        $this->assertTrue($this->accessor->supports($entity, $fieldMetadata));
    }

    /**
     * @dataProvider getValueDataProvider
     */
    public function testGetValue(object $entity, FieldMetadata $metadata, mixed $expectedValue)
    {
        $this->assertSame($expectedValue, $this->accessor->getValue($entity, $metadata));
    }

    public function getValueDataProvider(): array
    {
        return [
            'default' => [
                'entity' => new EntityStub('foo'),
                'metadata' => $this->getFieldMetadata('id'),
                'expected' => [],
            ]
        ];
    }

    /**
     * @dataProvider setValueDataProvider
     */
    public function testSetValue(?object $entity, FieldMetadata $metadata, ArrayCollection $values)
    {
        $this->accessor->setValue($entity, $metadata, $values);
        foreach ($values as $value) {
            $this->assertSame($entity, $value->getEntityStub());
        }
    }

    public function setValueDataProvider(): array
    {
        return [
            'default' => [
                'entity' => new EntityStub('foo'),
                'metadata' => $this->getFieldMetadata('entityStub'),
                'values' => new ArrayCollection([new CollectionItemStub('related-foo')]),
            ],
            'setter' => [
                'entity' => new EntityStub('foo', new EntityStub('bar')),
                'metadata' => $this->getFieldMetadata('entityStub', ['setter' => 'setEntityStub']),
                'values' => new ArrayCollection([new CollectionItemStub('related-foo')]),
            ],
            'reflection' => [
                'entity' => null,
                'metadata' => $this->getFieldMetadata('noGetter'),
                'values' => new ArrayCollection([new CollectionItemStub('related-foo')]),
            ],
        ];
    }

    private function getFieldMetadata(string $fieldName = null, array $options = []): FieldMetadata
    {
        $doctrineMetadata = $this->createMock(DoctrineMetadata::class);
        $doctrineMetadata->expects($this->any())
            ->method('getFieldName')
            ->willReturn($fieldName);

        $result = $this->createMock(FieldMetadata::class);
        $result->expects($this->any())
            ->method('getDoctrineMetadata')
            ->willReturn($doctrineMetadata);
        $result->expects($this->any())
            ->method('get')
            ->willReturnCallback(function ($code) use ($options) {
                $this->assertArrayHasKey($code, $options);

                return $options[$code];
            });
        $result->expects($this->any())
            ->method('has')
            ->willReturnCallback(function ($code) use ($options) {
                return isset($options[$code]);
            });

        return $result;
    }
}
