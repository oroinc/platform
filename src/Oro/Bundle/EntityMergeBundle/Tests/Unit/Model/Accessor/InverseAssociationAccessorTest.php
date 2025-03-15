<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Model\Accessor;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\EntityMergeBundle\Doctrine\DoctrineHelper;
use Oro\Bundle\EntityMergeBundle\Metadata\DoctrineMetadata;
use Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata;
use Oro\Bundle\EntityMergeBundle\Model\Accessor\InverseAssociationAccessor;
use Oro\Bundle\EntityMergeBundle\Tests\Unit\Stub\CollectionItemStub;
use Oro\Bundle\EntityMergeBundle\Tests\Unit\Stub\EntityStub;
use PHPUnit\Framework\TestCase;

class InverseAssociationAccessorTest extends TestCase
{
    private InverseAssociationAccessor $accessor;

    #[\Override]
    protected function setUp(): void
    {
        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::any())
            ->method('findBy')
            ->willReturn([]);

        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $doctrineHelper->expects(self::any())
            ->method('getEntityRepository')
            ->willReturn($repository);

        $this->accessor = new InverseAssociationAccessor(
            PropertyAccess::createPropertyAccessor(),
            $doctrineHelper
        );
    }

    private function getFieldMetadata(?string $fieldName = null, array $options = []): FieldMetadata
    {
        $doctrineMetadata = $this->createMock(DoctrineMetadata::class);
        $doctrineMetadata->expects(self::any())
            ->method('getFieldName')
            ->willReturn($fieldName);
        $doctrineMetadata->expects(self::any())
            ->method('get')
            ->with('sourceEntity')
            ->willReturn('Test\SourceEntity');

        $result = $this->createMock(FieldMetadata::class);
        $result->expects(self::any())
            ->method('getDoctrineMetadata')
            ->willReturn($doctrineMetadata);
        $result->expects(self::any())
            ->method('get')
            ->willReturnCallback(function ($code) use ($options) {
                self::assertArrayHasKey($code, $options);

                return $options[$code];
            });
        $result->expects(self::any())
            ->method('has')
            ->willReturnCallback(function ($code) use ($options) {
                return isset($options[$code]);
            });

        return $result;
    }

    public function testGetName(): void
    {
        self::assertEquals('inverse_association', $this->accessor->getName());
    }

    public function testSupportsFalseWhenDefinedBySourceEntity(): void
    {
        $entity = new EntityStub();
        $fieldMetadata = $this->createMock(FieldMetadata::class);

        $fieldMetadata->expects(self::once())
            ->method('isDefinedBySourceEntity')
            ->willReturn(true);

        self::assertFalse($this->accessor->supports($entity, $fieldMetadata));
    }

    public function testSupportsFalseWhenNotHasDoctrineMetadata(): void
    {
        $entity = new EntityStub();
        $fieldMetadata = $this->createMock(FieldMetadata::class);

        $fieldMetadata->expects(self::once())
            ->method('isDefinedBySourceEntity')
            ->willReturn(false);

        $fieldMetadata->expects(self::once())
            ->method('hasDoctrineMetadata')
            ->willReturn(false);

        self::assertFalse($this->accessor->supports($entity, $fieldMetadata));
    }

    public function testSupportsTrue(): void
    {
        $entity = new EntityStub();

        $doctrineMetadata = $this->createMock(DoctrineMetadata::class);
        $doctrineMetadata->expects(self::once())
            ->method('isManyToOne')
            ->willReturn(false);
        $doctrineMetadata->expects(self::once())
            ->method('isOneToOne')
            ->willReturn(true);

        $fieldMetadata = $this->createMock(FieldMetadata::class);
        $fieldMetadata->expects(self::once())
            ->method('isDefinedBySourceEntity')
            ->willReturn(false);
        $fieldMetadata->expects(self::once())
            ->method('hasDoctrineMetadata')
            ->willReturn(true);
        $fieldMetadata->expects(self::once())
            ->method('getDoctrineMetadata')
            ->willReturn($doctrineMetadata);

        self::assertTrue($this->accessor->supports($entity, $fieldMetadata));
    }

    public function testGetValue(): void
    {
        $entity = new EntityStub('foo');
        $metadata = $this->getFieldMetadata('id');
        $expected = [];

        self::assertSame($expected, $this->accessor->getValue($entity, $metadata));
    }

    /**
     * @dataProvider setValueDataProvider
     */
    public function testSetValue(?object $entity, FieldMetadata $metadata, ArrayCollection $values): void
    {
        $this->accessor->setValue($entity, $metadata, $values);
        foreach ($values as $value) {
            self::assertSame($entity, $value->getEntityStub());
        }
    }

    public function setValueDataProvider(): array
    {
        return [
            'default' => [
                'entity' => new EntityStub('foo'),
                'metadata' => $this->getFieldMetadata('entityStub'),
                'values' => new ArrayCollection([new CollectionItemStub('related-foo')])
            ],
            'setter' => [
                'entity' => new EntityStub('foo', new EntityStub('bar')),
                'metadata' => $this->getFieldMetadata('entityStub', ['setter' => 'setEntityStub']),
                'values' => new ArrayCollection([new CollectionItemStub('related-foo')])
            ],
            'reflection' => [
                'entity' => null,
                'metadata' => $this->getFieldMetadata('noGetter'),
                'values' => new ArrayCollection([new CollectionItemStub('related-foo')])
            ]
        ];
    }
}
