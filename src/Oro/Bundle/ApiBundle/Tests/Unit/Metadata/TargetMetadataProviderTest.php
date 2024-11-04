<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Metadata;

use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\MetaPropertyMetadata;
use Oro\Bundle\ApiBundle\Metadata\TargetMetadataProvider;
use Oro\Bundle\ApiBundle\Request\DocumentBuilder\ObjectAccessorInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class TargetMetadataProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ObjectAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $objectAccessor;

    /** @var TargetMetadataProvider */
    private $targetMetadataProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->objectAccessor = $this->createMock(ObjectAccessorInterface::class);

        $this->targetMetadataProvider = new TargetMetadataProvider($this->objectAccessor);
    }

    public function testGetTargetMetadataWhenObjectClassNameCannotBeGet(): void
    {
        $object = new \stdClass();
        $entityMetadata = $this->createMock(EntityMetadata::class);

        $this->objectAccessor->expects(self::once())
            ->method('getClassName')
            ->with(self::identicalTo($object))
            ->willReturn(null);
        $this->objectAccessor->expects(self::never())
            ->method('toArray');

        $entityMetadata->expects(self::never())
            ->method('getMetaProperties');
        $entityMetadata->expects(self::never())
            ->method('getIdentifierFieldNames');
        $entityMetadata->expects(self::never())
            ->method('getEntityMetadata');

        self::assertSame(
            $entityMetadata,
            $this->targetMetadataProvider->getTargetMetadata($object, $entityMetadata)
        );
    }

    public function testGetTargetMetadataWhenOnlyIdFieldsRequested(): void
    {
        $object = new \stdClass();
        $objectClassName = \stdClass::class;
        $entityMetadata = $this->createMock(EntityMetadata::class);

        $this->objectAccessor->expects(self::once())
            ->method('getClassName')
            ->with(self::identicalTo($object))
            ->willReturn($objectClassName);
        $this->objectAccessor->expects(self::once())
            ->method('toArray')
            ->with(self::identicalTo($object))
            ->willReturn(['id' => 1]);

        $entityMetadata->expects(self::once())
            ->method('getMetaProperties')
            ->willReturn([]);
        $entityMetadata->expects(self::once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $entityMetadata->expects(self::never())
            ->method('getEntityMetadata');

        self::assertSame(
            $entityMetadata,
            $this->targetMetadataProvider->getTargetMetadata($object, $entityMetadata)
        );
    }

    public function testGetTargetMetadataWhenOnlyIdFieldsAndMetaPropertiesRequested(): void
    {
        $object = new \stdClass();
        $objectClassName = \stdClass::class;
        $entityMetadata = $this->createMock(EntityMetadata::class);

        $this->objectAccessor->expects(self::once())
            ->method('getClassName')
            ->with(self::identicalTo($object))
            ->willReturn($objectClassName);
        $this->objectAccessor->expects(self::once())
            ->method('toArray')
            ->with(self::identicalTo($object))
            ->willReturn(['id' => 1, 'meta1' => 'metaVal1']);

        $entityMetadata->expects(self::once())
            ->method('getMetaProperties')
            ->willReturn([new MetaPropertyMetadata('meta1')]);
        $entityMetadata->expects(self::once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $entityMetadata->expects(self::never())
            ->method('getEntityMetadata');

        self::assertSame(
            $entityMetadata,
            $this->targetMetadataProvider->getTargetMetadata($object, $entityMetadata)
        );
    }

    public function testGetTargetMetadataWhenNotOnlyIdFieldsRequested(): void
    {
        $object = new \stdClass();
        $objectClassName = \stdClass::class;
        $entityMetadata = $this->createMock(EntityMetadata::class);
        $fullEntityMetadata = $this->createMock(EntityMetadata::class);

        $this->objectAccessor->expects(self::once())
            ->method('getClassName')
            ->with(self::identicalTo($object))
            ->willReturn($objectClassName);
        $this->objectAccessor->expects(self::once())
            ->method('toArray')
            ->with(self::identicalTo($object))
            ->willReturn(['id' => 1, 'field1' => 'val1']);

        $entityMetadata->expects(self::once())
            ->method('getMetaProperties')
            ->willReturn([]);
        $entityMetadata->expects(self::once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $entityMetadata->expects(self::once())
            ->method('getEntityMetadata')
            ->with($objectClassName)
            ->willReturn($fullEntityMetadata);

        self::assertSame(
            $fullEntityMetadata,
            $this->targetMetadataProvider->getTargetMetadata($object, $entityMetadata)
        );
    }

    public function testGetTargetMetadataWhenNotOnlyIdFieldsRequestedAndFullMetadataCannotBeGet(): void
    {
        $object = new \stdClass();
        $objectClassName = \stdClass::class;
        $entityMetadata = $this->createMock(EntityMetadata::class);

        $this->objectAccessor->expects(self::once())
            ->method('getClassName')
            ->with(self::identicalTo($object))
            ->willReturn($objectClassName);
        $this->objectAccessor->expects(self::once())
            ->method('toArray')
            ->with(self::identicalTo($object))
            ->willReturn(['id' => 1, 'field1' => 'val1']);

        $entityMetadata->expects(self::once())
            ->method('getMetaProperties')
            ->willReturn([]);
        $entityMetadata->expects(self::once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $entityMetadata->expects(self::once())
            ->method('getEntityMetadata')
            ->with($objectClassName)
            ->willReturn(null);

        self::assertSame(
            $entityMetadata,
            $this->targetMetadataProvider->getTargetMetadata($object, $entityMetadata)
        );
    }

    public function testGetAssociationTargetMetadataWhenObjectIsNull(): void
    {
        $object = null;
        $associationMetadata = $this->createMock(AssociationMetadata::class);
        $associationTargetEntityMetadata = $this->createMock(EntityMetadata::class);

        $this->objectAccessor->expects(self::never())
            ->method('getClassName');
        $this->objectAccessor->expects(self::never())
            ->method('toArray');

        $associationMetadata->expects(self::once())
            ->method('getTargetMetadata')
            ->with(self::isNull())
            ->willReturn($associationTargetEntityMetadata);

        $associationTargetEntityMetadata->expects(self::never())
            ->method('getMetaProperties');
        $associationTargetEntityMetadata->expects(self::never())
            ->method('getIdentifierFieldNames');

        self::assertSame(
            $associationTargetEntityMetadata,
            $this->targetMetadataProvider->getAssociationTargetMetadata($object, $associationMetadata)
        );
    }

    public function testGetAssociationTargetMetadataWhenObjectIsScalar(): void
    {
        $object = 'test';
        $associationMetadata = $this->createMock(AssociationMetadata::class);
        $associationTargetEntityMetadata = $this->createMock(EntityMetadata::class);

        $this->objectAccessor->expects(self::never())
            ->method('getClassName');
        $this->objectAccessor->expects(self::never())
            ->method('toArray');

        $associationMetadata->expects(self::once())
            ->method('getTargetMetadata')
            ->with(self::isNull())
            ->willReturn($associationTargetEntityMetadata);

        $associationTargetEntityMetadata->expects(self::never())
            ->method('getMetaProperties');
        $associationTargetEntityMetadata->expects(self::never())
            ->method('getIdentifierFieldNames');

        self::assertSame(
            $associationTargetEntityMetadata,
            $this->targetMetadataProvider->getAssociationTargetMetadata($object, $associationMetadata)
        );
    }

    public function testGetAssociationTargetMetadataWhenObjectClassNameCannotBeGet(): void
    {
        $object = new \stdClass();
        $associationMetadata = $this->createMock(AssociationMetadata::class);
        $associationTargetEntityMetadata = $this->createMock(EntityMetadata::class);

        $this->objectAccessor->expects(self::once())
            ->method('getClassName')
            ->with(self::identicalTo($object))
            ->willReturn(null);
        $this->objectAccessor->expects(self::never())
            ->method('toArray');

        $associationMetadata->expects(self::once())
            ->method('getTargetMetadata')
            ->with(self::isNull())
            ->willReturn($associationTargetEntityMetadata);

        $associationTargetEntityMetadata->expects(self::never())
            ->method('getMetaProperties');
        $associationTargetEntityMetadata->expects(self::never())
            ->method('getIdentifierFieldNames');

        self::assertSame(
            $associationTargetEntityMetadata,
            $this->targetMetadataProvider->getAssociationTargetMetadata($object, $associationMetadata)
        );
    }

    public function testGetAssociationTargetMetadataWhenTargetMetadataIsNull(): void
    {
        $object = new \stdClass();
        $objectClassName = \stdClass::class;
        $associationMetadata = $this->createMock(AssociationMetadata::class);

        $this->objectAccessor->expects(self::once())
            ->method('getClassName')
            ->with(self::identicalTo($object))
            ->willReturn($objectClassName);
        $this->objectAccessor->expects(self::never())
            ->method('toArray');

        $associationMetadata->expects(self::once())
            ->method('getTargetMetadata')
            ->with(self::isNull())
            ->willReturn(null);

        self::assertNull(
            $this->targetMetadataProvider->getAssociationTargetMetadata($object, $associationMetadata)
        );
    }

    public function testGetAssociationTargetMetadataWhenOnlyIdFieldsRequested(): void
    {
        $object = new \stdClass();
        $objectClassName = \stdClass::class;
        $associationMetadata = $this->createMock(AssociationMetadata::class);
        $associationTargetEntityMetadata = $this->createMock(EntityMetadata::class);

        $this->objectAccessor->expects(self::once())
            ->method('getClassName')
            ->with(self::identicalTo($object))
            ->willReturn($objectClassName);
        $this->objectAccessor->expects(self::once())
            ->method('toArray')
            ->with(self::identicalTo($object))
            ->willReturn(['id' => 1]);

        $associationMetadata->expects(self::once())
            ->method('getTargetMetadata')
            ->with(self::isNull())
            ->willReturn($associationTargetEntityMetadata);

        $associationTargetEntityMetadata->expects(self::once())
            ->method('getMetaProperties')
            ->willReturn([]);
        $associationTargetEntityMetadata->expects(self::once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);

        self::assertSame(
            $associationTargetEntityMetadata,
            $this->targetMetadataProvider->getAssociationTargetMetadata($object, $associationMetadata)
        );
    }

    public function testGetAssociationTargetMetadataWhenOnlyIdFieldsAndMetaPropertiesRequested(): void
    {
        $object = new \stdClass();
        $objectClassName = \stdClass::class;
        $associationMetadata = $this->createMock(AssociationMetadata::class);
        $associationTargetEntityMetadata = $this->createMock(EntityMetadata::class);

        $this->objectAccessor->expects(self::once())
            ->method('getClassName')
            ->with(self::identicalTo($object))
            ->willReturn($objectClassName);
        $this->objectAccessor->expects(self::once())
            ->method('toArray')
            ->with(self::identicalTo($object))
            ->willReturn(['id' => 1, 'meta1' => 'metaVal1']);

        $associationMetadata->expects(self::once())
            ->method('getTargetMetadata')
            ->with(self::isNull())
            ->willReturn($associationTargetEntityMetadata);

        $associationTargetEntityMetadata->expects(self::once())
            ->method('getMetaProperties')
            ->willReturn([new MetaPropertyMetadata('meta1')]);
        $associationTargetEntityMetadata->expects(self::once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);

        self::assertSame(
            $associationTargetEntityMetadata,
            $this->targetMetadataProvider->getAssociationTargetMetadata($object, $associationMetadata)
        );
    }

    public function testGetAssociationTargetMetadataWhenNotOnlyIdFieldsRequested(): void
    {
        $object = new \stdClass();
        $objectClassName = \stdClass::class;
        $associationMetadata = $this->createMock(AssociationMetadata::class);
        $associationTargetEntityMetadata = $this->createMock(EntityMetadata::class);
        $associationTargetFullEntityMetadata = $this->createMock(EntityMetadata::class);

        $this->objectAccessor->expects(self::once())
            ->method('getClassName')
            ->with(self::identicalTo($object))
            ->willReturn($objectClassName);
        $this->objectAccessor->expects(self::once())
            ->method('toArray')
            ->with(self::identicalTo($object))
            ->willReturn(['id' => 1, 'field1' => 'val1']);

        $associationMetadata->expects(self::exactly(2))
            ->method('getTargetMetadata')
            ->willReturnMap([
                [null, $associationTargetEntityMetadata],
                [$objectClassName, $associationTargetFullEntityMetadata]
            ]);

        $associationTargetEntityMetadata->expects(self::once())
            ->method('getMetaProperties')
            ->willReturn([]);
        $associationTargetEntityMetadata->expects(self::once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);

        self::assertSame(
            $associationTargetFullEntityMetadata,
            $this->targetMetadataProvider->getAssociationTargetMetadata($object, $associationMetadata)
        );
    }

    public function testGetAssociationTargetMetadataWhenNotOnlyIdFieldsRequestedAndFullMetadataCannotBeGet(): void
    {
        $object = new \stdClass();
        $objectClassName = \stdClass::class;
        $associationMetadata = $this->createMock(AssociationMetadata::class);
        $associationTargetEntityMetadata = $this->createMock(EntityMetadata::class);

        $this->objectAccessor->expects(self::once())
            ->method('getClassName')
            ->with(self::identicalTo($object))
            ->willReturn($objectClassName);
        $this->objectAccessor->expects(self::once())
            ->method('toArray')
            ->with(self::identicalTo($object))
            ->willReturn(['id' => 1, 'field1' => 'val1']);

        $associationMetadata->expects(self::exactly(2))
            ->method('getTargetMetadata')
            ->willReturnMap([
                [null, $associationTargetEntityMetadata],
                [$objectClassName, null]
            ]);

        $associationTargetEntityMetadata->expects(self::once())
            ->method('getMetaProperties')
            ->willReturn([]);
        $associationTargetEntityMetadata->expects(self::once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);

        self::assertSame(
            $associationTargetEntityMetadata,
            $this->targetMetadataProvider->getAssociationTargetMetadata($object, $associationMetadata)
        );
    }
}
