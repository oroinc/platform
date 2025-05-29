<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Form\DataTransformer;

use Oro\Bundle\ApiBundle\Collection\IncludedEntityCollection;
use Oro\Bundle\ApiBundle\Collection\IncludedEntityData;
use Oro\Bundle\ApiBundle\Form\DataTransformer\EntityToIdTransformer;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\CompositeKeyEntity;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\UserProfile;
use Oro\Bundle\ApiBundle\Tests\Unit\OrmRelatedTestCase;
use Oro\Bundle\ApiBundle\Util\EntityLoader;
use Oro\Bundle\ApiBundle\Util\EntityMapper;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class EntityToIdTransformerTest extends OrmRelatedTestCase
{
    private EntityLoader&MockObject $entityLoader;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->entityLoader = $this->createMock(EntityLoader::class);
    }

    private function getEntityToIdTransformer(
        AssociationMetadata $metadata,
        ?EntityMapper $entityMapper = null,
        ?IncludedEntityCollection $includedEntities = null
    ): EntityToIdTransformer {
        return new EntityToIdTransformer(
            $this->doctrineHelper,
            $this->entityLoader,
            $metadata,
            $entityMapper,
            $includedEntities
        );
    }

    public function testTransform()
    {
        $transformer = $this->getEntityToIdTransformer($this->createMock(AssociationMetadata::class));

        self::assertNull($transformer->transform(new \stdClass()));
    }

    /**
     * @dataProvider reverseTransformForEmptyValueDataProvider
     */
    public function testReverseTransformForEmptyValue(array|string|null $value)
    {
        $metadata = $this->createMock(AssociationMetadata::class);
        $metadata->expects(self::never())
            ->method(self::anything());

        $transformer = $this->getEntityToIdTransformer($metadata);

        self::assertNull($transformer->reverseTransform($value));
    }

    public function reverseTransformForEmptyValueDataProvider(): array
    {
        return [
            [null],
            [''],
            [[]]
        ];
    }

    /**
     * @dataProvider reverseTransformDataProvider
     */
    public function testReverseTransform(array $value)
    {
        $metadata = $this->createMock(AssociationMetadata::class);
        $metadata->expects(self::once())
            ->method('getAcceptableTargetClassNames')
            ->willReturn([Group::class]);

        $entityMetadata = $this->createMock(EntityMetadata::class);
        $metadata->expects(self::once())
            ->method('getTargetMetadata')
            ->with(Group::class)
            ->willReturn($entityMetadata);

        $entity = new Group();
        $entity->setId($value['id']);
        $entity->setName('test');

        $this->entityLoader->expects(self::once())
            ->method('findEntity')
            ->with($value['class'], $value['id'], self::identicalTo($entityMetadata))
            ->willReturn($entity);

        $transformer = $this->getEntityToIdTransformer($metadata);

        self::assertEquals($entity, $transformer->reverseTransform($value));
    }

    public function reverseTransformDataProvider(): array
    {
        return [
            [['class' => Group::class, 'id' => 0]],
            [['class' => Group::class, 'id' => '0']],
            [['class' => Group::class, 'id' => 123]],
        ];
    }

    public function testReverseTransformForModelInheritedFromManageableEntity()
    {
        $this->notManageableClassNames = [UserProfile::class];

        $metadata = $this->createMock(AssociationMetadata::class);
        $metadata->expects(self::once())
            ->method('getAcceptableTargetClassNames')
            ->willReturn([UserProfile::class]);

        $value = ['class' => UserProfile::class, 'id' => 123];
        $entity = new User();
        $entity->setId($value['id']);

        $this->entityLoader->expects(self::once())
            ->method('findEntity')
            ->with(User::class, $value['id'], self::isNull())
            ->willReturn($entity);

        $transformer = $this->getEntityToIdTransformer($metadata);

        $transformResult = $transformer->reverseTransform($value);
        self::assertEquals(User::class, get_class($transformResult));
        self::assertEquals($value['id'], $transformResult->getId());
    }

    public function testReverseTransformWhenEntityDoesNotFoundInIncludedEntity()
    {
        $metadata = $this->createMock(AssociationMetadata::class);
        $metadata->expects(self::once())
            ->method('getAcceptableTargetClassNames')
            ->willReturn([Group::class]);

        $value = ['class' => Group::class, 'id' => 123];
        $entity = new Group();
        $entity->setId($value['id']);
        $entity->setName('test');

        $includedEntities = new IncludedEntityCollection();

        $this->entityLoader->expects(self::once())
            ->method('findEntity')
            ->with($value['class'], $value['id'], self::isNull())
            ->willReturn($entity);

        $transformer = $this->getEntityToIdTransformer($metadata, null, $includedEntities);

        self::assertEquals($entity, $transformer->reverseTransform($value));
    }

    public function testReverseTransformWhenEntityFoundInIncludedEntity()
    {
        $metadata = $this->createMock(AssociationMetadata::class);
        $metadata->expects(self::once())
            ->method('getAcceptableTargetClassNames')
            ->willReturn([Group::class]);

        $value = ['class' => Group::class, 'id' => 123];
        $entity = new Group();
        $entity->setId($value['id']);
        $entity->setName('test');

        $includedEntities = new IncludedEntityCollection();
        $includedEntities->add($entity, $value['class'], $value['id'], new IncludedEntityData('/included/0', 0));

        $transformer = $this->getEntityToIdTransformer($metadata, null, $includedEntities);

        self::assertEquals($entity, $transformer->reverseTransform($value));
    }

    public function testReverseTransformWhenEntityIsPrimaryEntity()
    {
        $metadata = $this->createMock(AssociationMetadata::class);
        $metadata->expects(self::once())
            ->method('getAcceptableTargetClassNames')
            ->willReturn([Group::class]);

        $value = ['class' => Group::class, 'id' => 123];
        $entity = new Group();
        $entity->setId($value['id']);
        $entity->setName('test');

        $includedEntities = new IncludedEntityCollection();
        $includedEntities->setPrimaryEntityId($value['class'], $value['id']);
        $includedEntities->setPrimaryEntity($entity, null);

        $transformer = $this->getEntityToIdTransformer($metadata, null, $includedEntities);

        self::assertEquals($entity, $transformer->reverseTransform($value));
    }

    public function testReverseTransformWhenEntityNotFound()
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage(
            'An "Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group" entity with "123" identifier does not exist.'
        );

        $metadata = $this->createMock(AssociationMetadata::class);
        $metadata->expects(self::once())
            ->method('getAcceptableTargetClassNames')
            ->willReturn([Group::class]);

        $value = ['class' => Group::class, 'id' => 123];

        $this->entityLoader->expects(self::once())
            ->method('findEntity')
            ->with($value['class'], $value['id'], self::isNull())
            ->willReturn(null);

        $transformer = $this->getEntityToIdTransformer($metadata);

        $transformer->reverseTransform($value);
    }

    public function testReverseTransformWhenEntityWithCompositeKeyNotFound()
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage(sprintf(
            'An "%s" entity with "array(id = 123, title = test)" identifier does not exist.',
            CompositeKeyEntity::class
        ));

        $metadata = $this->createMock(AssociationMetadata::class);
        $metadata->expects(self::once())
            ->method('getAcceptableTargetClassNames')
            ->willReturn([CompositeKeyEntity::class]);

        $value = [
            'class' => CompositeKeyEntity::class,
            'id'    => ['id' => 123, 'title' => 'test']
        ];

        $this->entityLoader->expects(self::once())
            ->method('findEntity')
            ->with($value['class'], $value['id'], self::isNull())
            ->willReturn(null);

        $transformer = $this->getEntityToIdTransformer($metadata);

        $transformer->reverseTransform($value);
    }

    public function testReverseTransformWhenInvalidValueType()
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('Expected an array.');

        $metadata = $this->createMock(AssociationMetadata::class);
        $metadata->expects(self::never())
            ->method(self::anything());

        $transformer = $this->getEntityToIdTransformer($metadata);

        $transformer->reverseTransform(123);
    }

    public function testReverseTransformWhenValueDoesNotHaveClass()
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('Expected an array with "class" element.');

        $metadata = $this->createMock(AssociationMetadata::class);
        $metadata->expects(self::never())
            ->method(self::anything());

        $transformer = $this->getEntityToIdTransformer($metadata);

        $transformer->reverseTransform(['id' => 123]);
    }

    public function testReverseTransformWhenValueDoesNotHaveId()
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('Expected an array with "id" element.');

        $metadata = $this->createMock(AssociationMetadata::class);
        $metadata->expects(self::once())
            ->method('getAcceptableTargetClassNames')
            ->willReturn([Group::class]);

        $transformer = $this->getEntityToIdTransformer($metadata);

        $transformer->reverseTransform(['class' => Group::class]);
    }

    public function testReverseTransformWhenAnyEntityTypeShouldBeRejected()
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('There are no acceptable classes.');

        $metadata = $this->createMock(AssociationMetadata::class);
        $metadata->expects(self::once())
            ->method('getAcceptableTargetClassNames')
            ->willReturn([]);
        $metadata->expects(self::once())
            ->method('isEmptyAcceptableTargetsAllowed')
            ->willReturn(false);

        $transformer = $this->getEntityToIdTransformer($metadata);

        $transformer->reverseTransform(['class' => Group::class, 'id' => ['primary' => 1]]);
    }

    public function testReverseTransformForNotAcceptableEntity()
    {
        $this->notManageableClassNames = [User::class];

        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage(sprintf(
            'The "%s" class is not acceptable. Acceptable classes: %s.',
            User::class,
            Group::class
        ));

        $metadata = $this->createMock(AssociationMetadata::class);
        $metadata->expects(self::once())
            ->method('getAcceptableTargetClassNames')
            ->willReturn([Group::class]);

        $transformer = $this->getEntityToIdTransformer($metadata);

        $transformer->reverseTransform(['class' => User::class, 'id' => 123]);
    }

    public function testReverseTransformForNotManageableEntity()
    {
        $this->notManageableClassNames = [Group::class];

        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage(
            'The "Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group" class must be a managed Doctrine entity.'
        );

        $metadata = $this->createMock(AssociationMetadata::class);
        $metadata->expects(self::once())
            ->method('getAcceptableTargetClassNames')
            ->willReturn([Group::class]);

        $transformer = $this->getEntityToIdTransformer($metadata);

        $transformer->reverseTransform(['class' => Group::class, 'id' => 123]);
    }

    public function testReverseTransformWhenAnyEntityTypeIsAcceptable()
    {
        $metadata = $this->createMock(AssociationMetadata::class);
        $metadata->expects(self::once())
            ->method('getAcceptableTargetClassNames')
            ->willReturn([]);
        $metadata->expects(self::once())
            ->method('isEmptyAcceptableTargetsAllowed')
            ->willReturn(true);

        $value = ['class' => Group::class, 'id' => 123];
        $entity = new Group();
        $entity->setId($value['id']);
        $entity->setName('test');

        $this->entityLoader->expects(self::once())
            ->method('findEntity')
            ->with($value['class'], $value['id'], self::isNull())
            ->willReturn($entity);

        $transformer = $this->getEntityToIdTransformer($metadata);

        self::assertEquals($entity, $transformer->reverseTransform($value));
    }

    public function testReverseTransformWhenDoctrineIsNotAbleToLoadEntity()
    {
        $loadException = new TransformationFailedException(
            'An "%s" entity with "array(primary = 1)" identifier cannot be loaded.'
        );

        $this->expectExceptionObject($loadException);

        $metadata = $this->createMock(AssociationMetadata::class);
        $metadata->expects(self::once())
            ->method('getAcceptableTargetClassNames')
            ->willReturn([Group::class]);

        $value = ['class' => Group::class, 'id' => ['primary' => 1]];

        $this->entityLoader->expects(self::once())
            ->method('findEntity')
            ->with($value['class'], $value['id'], self::isNull())
            ->willThrowException($loadException);

        $transformer = $this->getEntityToIdTransformer($metadata);

        $transformer->reverseTransform($value);
    }

    /**
     * @dataProvider reverseTransformWhenIdIsNotAcceptableDataProvider
     */
    public function testReverseTransformWhenIdIsNotAcceptable(mixed $id)
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage(
            'The "id" element is expected to be an integer, non-empty string or non-empty array.'
        );

        $metadata = $this->createMock(AssociationMetadata::class);
        $metadata->expects(self::once())
            ->method('getAcceptableTargetClassNames')
            ->willReturn([Group::class]);

        $value = ['class' => Group::class, 'id' => $id];

        $transformer = $this->getEntityToIdTransformer($metadata);

        $transformer->reverseTransform($value);
    }

    public function reverseTransformWhenIdIsNotAcceptableDataProvider(): array
    {
        return [
            [null],
            [''],
            ['  '],
            [[]],
        ];
    }
}
