<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Form\DataTransformer;

use Oro\Bundle\ApiBundle\Collection\IncludedEntityCollection;
use Oro\Bundle\ApiBundle\Collection\IncludedEntityData;
use Oro\Bundle\ApiBundle\Form\DataTransformer\EntityToIdTransformer;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\CompositeKeyEntity;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\UserProfile;
use Oro\Bundle\ApiBundle\Tests\Unit\OrmRelatedTestCase;
use Oro\Bundle\ApiBundle\Util\EntityLoader;
use Oro\Bundle\ApiBundle\Util\EntityMapper;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class EntityToIdTransformerTest extends OrmRelatedTestCase
{
    private function getEntityToIdTransformer(
        AssociationMetadata $metadata,
        EntityMapper $entityMapper = null,
        IncludedEntityCollection $includedEntities = null
    ): EntityToIdTransformer {
        return new EntityToIdTransformer(
            $this->doctrineHelper,
            new EntityLoader($this->doctrine),
            $metadata,
            $entityMapper,
            $includedEntities
        );
    }

    private function getAssociationMetadata(array $acceptableTargetClassNames = []): AssociationMetadata
    {
        $metadata = new AssociationMetadata();
        $metadata->setAcceptableTargetClassNames($acceptableTargetClassNames);

        return $metadata;
    }

    public function testTransform()
    {
        $metadata = $this->getAssociationMetadata([Group::class]);
        $transformer = $this->getEntityToIdTransformer($metadata);
        self::assertNull($transformer->transform(new \stdClass()));
    }

    /**
     * @dataProvider reverseTransformForEmptyValueDataProvider
     */
    public function testReverseTransformForEmptyValue(array|string|null $value)
    {
        $metadata = $this->getAssociationMetadata([Group::class]);
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
        $metadata = $this->getAssociationMetadata([Group::class]);
        $transformer = $this->getEntityToIdTransformer($metadata);

        $entity = new Group();
        $entity->setId($value['id']);
        $entity->setName('test');

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT t0.id AS id_1, t0.name AS name_2 FROM group_table t0 WHERE t0.id = ?',
            [
                [
                    'id_1'   => $entity->getId(),
                    'name_2' => $entity->getName()
                ]
            ],
            [1 => $value['id']],
            [1 => \PDO::PARAM_INT]
        );

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

        $metadata = $this->getAssociationMetadata([UserProfile::class]);
        $transformer = $this->getEntityToIdTransformer($metadata);

        $value = ['class' => UserProfile::class, 'id' => 123];

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT t0.id AS id_1, t0.name AS name_2,'
            . ' t0.category_name AS category_name_3, t0.owner_id AS owner_id_4'
            . ' FROM user_table t0 WHERE t0.id = ?',
            [
                [
                    'id_1'            => $value['id'],
                    'name_2'          => null,
                    'category_name_3' => null,
                    'owner_id_4'      => null
                ]
            ],
            [1 => $value['id']],
            [1 => \PDO::PARAM_INT]
        );

        $transformResult = $transformer->reverseTransform($value);
        self::assertEquals(User::class, get_class($transformResult));
        self::assertEquals($value['id'], $transformResult->getId());
    }

    public function testReverseTransformWhenEntityDoesNotFoundInIncludedEntity()
    {
        $metadata = $this->getAssociationMetadata([Group::class]);
        $includedEntities = new IncludedEntityCollection();
        $transformer = $this->getEntityToIdTransformer($metadata, null, $includedEntities);

        $value = ['class' => Group::class, 'id' => 123];
        $entity = new Group();
        $entity->setId($value['id']);
        $entity->setName('test');

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT t0.id AS id_1, t0.name AS name_2 FROM group_table t0 WHERE t0.id = ?',
            [
                [
                    'id_1'   => $entity->getId(),
                    'name_2' => $entity->getName()
                ]
            ],
            [1 => $value['id']],
            [1 => \PDO::PARAM_INT]
        );

        self::assertEquals($entity, $transformer->reverseTransform($value));
    }

    public function testReverseTransformWhenEntityFoundInIncludedEntity()
    {
        $metadata = $this->getAssociationMetadata([Group::class]);
        $includedEntities = new IncludedEntityCollection();
        $transformer = $this->getEntityToIdTransformer($metadata, null, $includedEntities);

        $value = ['class' => Group::class, 'id' => 123];
        $entity = new Group();
        $entity->setId($value['id']);
        $entity->setName('test');

        $includedEntities->add($entity, $value['class'], $value['id'], new IncludedEntityData('/included/0', 0));

        self::assertEquals($entity, $transformer->reverseTransform($value));
    }

    public function testReverseTransformWhenEntityIsPrimaryEntity()
    {
        $metadata = $this->getAssociationMetadata([Group::class]);
        $includedEntities = new IncludedEntityCollection();
        $transformer = $this->getEntityToIdTransformer($metadata, null, $includedEntities);

        $value = ['class' => Group::class, 'id' => 123];
        $entity = new Group();
        $entity->setId($value['id']);
        $entity->setName('test');

        $includedEntities->setPrimaryEntityId($value['class'], $value['id']);
        $includedEntities->setPrimaryEntity($entity, null);

        self::assertEquals($entity, $transformer->reverseTransform($value));
    }

    public function testReverseTransformWhenEntityNotFound()
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage(
            'An "Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group" entity with "123" identifier does not exist.'
        );

        $metadata = $this->getAssociationMetadata([Group::class]);
        $transformer = $this->getEntityToIdTransformer($metadata);

        $value = ['class' => Group::class, 'id' => 123];

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT t0.id AS id_1, t0.name AS name_2 FROM group_table t0 WHERE t0.id = ?',
            [],
            [1 => $value['id']],
            [1 => \PDO::PARAM_INT]
        );

        $transformer->reverseTransform($value);
    }

    public function testReverseTransformWhenEntityWithCompositeKeyNotFound()
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage(sprintf(
            'An "%s" entity with "array(id = 123, title = test)" identifier does not exist.',
            CompositeKeyEntity::class
        ));

        $metadata = $this->getAssociationMetadata([CompositeKeyEntity::class]);
        $transformer = $this->getEntityToIdTransformer($metadata);

        $value = [
            'class' => CompositeKeyEntity::class,
            'id'    => ['id' => 123, 'title' => 'test']
        ];

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT t0.id AS id_1, t0.title AS title_2'
            . ' FROM composite_key_entity t0'
            . ' WHERE t0.id = ? AND t0.title = ?',
            [],
            [1 => $value['id']['id'], 2 => $value['id']['title']],
            [1 => \PDO::PARAM_INT, 2 => \PDO::PARAM_STR]
        );

        $transformer->reverseTransform($value);
    }

    public function testReverseTransformWhenInvalidValueType()
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('Expected an array.');

        $metadata = $this->getAssociationMetadata([Group::class]);
        $transformer = $this->getEntityToIdTransformer($metadata);
        $transformer->reverseTransform(123);
    }

    public function testReverseTransformWhenValueDoesNotHaveClass()
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('Expected an array with "class" element.');

        $metadata = $this->getAssociationMetadata([Group::class]);
        $transformer = $this->getEntityToIdTransformer($metadata);
        $transformer->reverseTransform(['id' => 123]);
    }

    public function testReverseTransformWhenValueDoesNotHaveId()
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('Expected an array with "id" element.');

        $metadata = $this->getAssociationMetadata([Group::class]);
        $transformer = $this->getEntityToIdTransformer($metadata);
        $transformer->reverseTransform(['class' => Group::class]);
    }

    public function testReverseTransformWhenAnyEntityTypeShouldBeRejected()
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('There are no acceptable classes.');

        $metadata = $this->getAssociationMetadata([]);
        $metadata->setEmptyAcceptableTargetsAllowed(false);
        $transformer = $this->getEntityToIdTransformer($metadata);
        $transformer->reverseTransform(['class' => Group::class, 'id' => ['primary' => 1]]);
    }

    public function testReverseTransformForNotAcceptableEntity()
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage(sprintf(
            'The "%s" class is not acceptable. Acceptable classes: %s.',
            User::class,
            Group::class
        ));

        $metadata = $this->getAssociationMetadata([Group::class]);
        $transformer = $this->getEntityToIdTransformer($metadata);

        $this->notManageableClassNames = [User::class];
        $transformer->reverseTransform(['class' => User::class, 'id' => 123]);
    }

    public function testReverseTransformForNotManageableEntity()
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage(
            'The "Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group" class must be a managed Doctrine entity.'
        );

        $metadata = $this->getAssociationMetadata([Group::class]);
        $transformer = $this->getEntityToIdTransformer($metadata);

        $this->notManageableClassNames = [Group::class];
        $transformer->reverseTransform(['class' => Group::class, 'id' => 123]);
    }

    public function testReverseTransformWhenAnyEntityTypeIsAcceptable()
    {
        $metadata = $this->getAssociationMetadata([]);
        $transformer = $this->getEntityToIdTransformer($metadata);

        $value = ['class' => Group::class, 'id' => 123];
        $entity = new Group();
        $entity->setId($value['id']);
        $entity->setName('test');

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT t0.id AS id_1, t0.name AS name_2 FROM group_table t0 WHERE t0.id = ?',
            [
                [
                    'id_1'   => $entity->getId(),
                    'name_2' => $entity->getName()
                ]
            ],
            [1 => $value['id']],
            [1 => \PDO::PARAM_INT]
        );

        self::assertEquals($entity, $transformer->reverseTransform($value));
    }

    public function testReverseTransformWhenDoctrineIsNotAbleToLoadEntity()
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage(sprintf(
            'An "%s" entity with "array(primary = 1)" identifier cannot be loaded.',
            Group::class
        ));

        $metadata = $this->getAssociationMetadata([Group::class]);
        $transformer = $this->getEntityToIdTransformer($metadata);

        $value = ['class' => Group::class, 'id' => ['primary' => 1]];

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

        $metadata = $this->getAssociationMetadata([Group::class]);
        $transformer = $this->getEntityToIdTransformer($metadata);
        $value = ['class' => Group::class, 'id' => $id];
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
