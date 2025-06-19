<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Form\DataTransformer;

use Oro\Bundle\ApiBundle\Form\DataTransformer\NestedAssociationTransformer;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\EntityIdentifier;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\CompositeKeyEntity;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\UserProfile;
use Oro\Bundle\ApiBundle\Tests\Unit\OrmRelatedTestCase;
use Oro\Bundle\ApiBundle\Util\EntityLoader;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class NestedAssociationTransformerTest extends OrmRelatedTestCase
{
    private EntityLoader&MockObject $entityLoader;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->entityLoader = $this->createMock(EntityLoader::class);
    }

    private function getNestedAssociationTransformer(AssociationMetadata $metadata): NestedAssociationTransformer
    {
        return new NestedAssociationTransformer(
            $this->doctrineHelper,
            $this->entityLoader,
            $metadata
        );
    }

    public function testTransform(): void
    {
        $transformer = $this->getNestedAssociationTransformer($this->createMock(AssociationMetadata::class));

        self::assertNull($transformer->transform(new \stdClass()));
    }

    /**
     * @dataProvider reverseTransformForEmptyValueDataProvider
     */
    public function testReverseTransformForEmptyValue(array|string|null $value): void
    {
        $metadata = $this->createMock(AssociationMetadata::class);
        $metadata->expects(self::never())
            ->method(self::anything());

        $transformer = $this->getNestedAssociationTransformer($metadata);

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

    public function testReverseTransform(): void
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

        $value = ['class' => Group::class, 'id' => 123];
        $entity = new Group();
        $entity->setId($value['id']);
        $entity->setName('test');

        $this->entityLoader->expects(self::once())
            ->method('findEntity')
            ->with($value['class'], $value['id'], self::identicalTo($entityMetadata))
            ->willReturn($entity);

        $transformer = $this->getNestedAssociationTransformer($metadata);

        self::assertEquals(
            new EntityIdentifier($entity->getId(), get_class($entity)),
            $transformer->reverseTransform($value)
        );
    }

    public function testReverseTransformForModelInheritedFromManageableEntity(): void
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

        $transformer = $this->getNestedAssociationTransformer($metadata);

        self::assertEquals(
            new EntityIdentifier($entity->getId(), UserProfile::class),
            $transformer->reverseTransform($value)
        );
    }

    public function testReverseTransformForEntityWithCompositeKey(): void
    {
        $metadata = $this->createMock(AssociationMetadata::class);
        $metadata->expects(self::once())
            ->method('getAcceptableTargetClassNames')
            ->willReturn([CompositeKeyEntity::class]);

        $value = [
            'class' => CompositeKeyEntity::class,
            'id'    => ['id' => 123, 'title' => 'test']
        ];

        $entity = new CompositeKeyEntity();
        $entity->setId($value['id']['id']);
        $entity->setTitle($value['id']['title']);

        $this->entityLoader->expects(self::once())
            ->method('findEntity')
            ->with($value['class'], $value['id'], self::isNull())
            ->willReturn($entity);

        $transformer = $this->getNestedAssociationTransformer($metadata);

        self::assertEquals(
            new EntityIdentifier(['id' => $entity->getId(), 'title' => $entity->getTitle()], get_class($entity)),
            $transformer->reverseTransform($value)
        );
    }

    public function testReverseTransformWhenEntityNotFound(): void
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

        $transformer = $this->getNestedAssociationTransformer($metadata);

        $transformer->reverseTransform($value);
    }

    public function testReverseTransformWhenEntityWithCompositeKeyNotFound(): void
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

        $transformer = $this->getNestedAssociationTransformer($metadata);

        $transformer->reverseTransform($value);
    }

    public function testReverseTransformWhenInvalidValueType(): void
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('Expected an array.');

        $metadata = $this->createMock(AssociationMetadata::class);
        $metadata->expects(self::never())
            ->method(self::anything());

        $transformer = $this->getNestedAssociationTransformer($metadata);

        $transformer->reverseTransform(123);
    }

    public function testReverseTransformWhenValueDoesNotHaveClass(): void
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('Expected an array with "class" element.');

        $metadata = $this->createMock(AssociationMetadata::class);
        $metadata->expects(self::never())
            ->method(self::anything());

        $transformer = $this->getNestedAssociationTransformer($metadata);

        $transformer->reverseTransform(['id' => 123]);
    }

    public function testReverseTransformWhenValueDoesNotHaveId(): void
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('Expected an array with "id" element.');

        $metadata = $this->createMock(AssociationMetadata::class);
        $metadata->expects(self::once())
            ->method('getAcceptableTargetClassNames')
            ->willReturn([Group::class]);

        $transformer = $this->getNestedAssociationTransformer($metadata);

        $transformer->reverseTransform(['class' => Group::class]);
    }

    public function testReverseTransformWhenAnyEntityTypeShouldBeRejected(): void
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

        $transformer = $this->getNestedAssociationTransformer($metadata);

        $transformer->reverseTransform(['class' => Group::class, 'id' => 123]);
    }

    public function testReverseTransformForNotAcceptableEntity(): void
    {
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

        $transformer = $this->getNestedAssociationTransformer($metadata);

        $transformer->reverseTransform(['class' => User::class, 'id' => 123]);
    }

    public function testReverseTransformForNotManageableEntity(): void
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

        $transformer = $this->getNestedAssociationTransformer($metadata);

        $transformer->reverseTransform(['class' => Group::class, 'id' => 123]);
    }

    public function testReverseTransformWhenAnyEntityTypeIsAcceptable(): void
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

        $transformer = $this->getNestedAssociationTransformer($metadata);

        self::assertEquals(
            new EntityIdentifier($entity->getId(), get_class($entity)),
            $transformer->reverseTransform($value)
        );
    }

    public function testReverseTransformWhenDoctrineIsNotAbleToLoadEntity(): void
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

        $transformer = $this->getNestedAssociationTransformer($metadata);

        $transformer->reverseTransform($value);
    }
}
