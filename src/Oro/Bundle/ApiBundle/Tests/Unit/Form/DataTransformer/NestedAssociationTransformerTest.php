<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Form\DataTransformer;

use Oro\Bundle\ApiBundle\Form\DataTransformer\NestedAssociationTransformer;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Model\EntityIdentifier;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\CompositeKeyEntity;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\UserProfile;
use Oro\Bundle\ApiBundle\Tests\Unit\OrmRelatedTestCase;
use Oro\Bundle\ApiBundle\Util\EntityLoader;

class NestedAssociationTransformerTest extends OrmRelatedTestCase
{
    /**
     * @param AssociationMetadata $metadata
     *
     * @return NestedAssociationTransformer
     */
    private function getNestedAssociationTransformer(AssociationMetadata $metadata)
    {
        return new NestedAssociationTransformer(
            $this->doctrineHelper,
            new EntityLoader($this->doctrine),
            $metadata
        );
    }

    /**
     * @param string[] $acceptableTargetClassNames
     *
     * @return AssociationMetadata
     */
    private function getAssociationMetadata(array $acceptableTargetClassNames = [])
    {
        $metadata = new AssociationMetadata();
        $metadata->setAcceptableTargetClassNames($acceptableTargetClassNames);

        return $metadata;
    }

    public function testTransform()
    {
        $metadata = $this->getAssociationMetadata([Group::class]);
        $transformer = $this->getNestedAssociationTransformer($metadata);
        self::assertNull($transformer->transform(new \stdClass()));
    }

    /**
     * @dataProvider reverseTransformForEmptyValueDataProvider
     */
    public function testReverseTransformForEmptyValue($value, $expected)
    {
        $metadata = $this->getAssociationMetadata([Group::class]);
        $transformer = $this->getNestedAssociationTransformer($metadata);
        self::assertEquals($expected, $transformer->reverseTransform($value));
    }

    public function reverseTransformForEmptyValueDataProvider()
    {
        return [
            [null, null],
            ['', null],
            [[], null]
        ];
    }

    public function testReverseTransform()
    {
        $metadata = $this->getAssociationMetadata([Group::class]);
        $transformer = $this->getNestedAssociationTransformer($metadata);

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

        self::assertEquals(
            new EntityIdentifier($entity->getId(), get_class($entity)),
            $transformer->reverseTransform($value)
        );
    }

    public function testReverseTransformForModelInheritedFromManageableEntity()
    {
        $this->notManageableClassNames = [UserProfile::class];

        $metadata = $this->getAssociationMetadata([UserProfile::class]);
        $transformer = $this->getNestedAssociationTransformer($metadata);

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

        self::assertEquals(
            new EntityIdentifier($value['id'], UserProfile::class),
            $transformer->reverseTransform($value)
        );
    }

    public function testReverseTransformForEntityWithCompositeKey()
    {
        $metadata = $this->getAssociationMetadata([CompositeKeyEntity::class]);
        $transformer = $this->getNestedAssociationTransformer($metadata);

        $value = [
            'class' => CompositeKeyEntity::class,
            'id'    => ['id' => 123, 'title' => 'test']
        ];

        $entity = new CompositeKeyEntity();
        $entity->setId($value['id']['id']);
        $entity->setTitle($value['id']['title']);

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT t0.id AS id_1, t0.title AS title_2'
            . ' FROM composite_key_entity t0'
            . ' WHERE t0.id = ? AND t0.title = ?',
            [
                [
                    'id_1'    => $entity->getId(),
                    'title_2' => $entity->getTitle()
                ]
            ],
            [1 => $value['id']['id'], 2 => $value['id']['title']],
            [1 => \PDO::PARAM_INT, 2 => \PDO::PARAM_STR]
        );

        self::assertEquals(
            new EntityIdentifier(['id' => $entity->getId(), 'title' => $entity->getTitle()], get_class($entity)),
            $transformer->reverseTransform($value)
        );
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Symfony\Component\Form\Exception\TransformationFailedException
     * @expectedExceptionMessage An "Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group" entity with "123" identifier does not exist.
     */
    // @codingStandardsIgnoreEnd
    public function testReverseTransformWhenEntityNotFound()
    {
        $metadata = $this->getAssociationMetadata([Group::class]);
        $transformer = $this->getNestedAssociationTransformer($metadata);

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

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Symfony\Component\Form\Exception\TransformationFailedException
     * @expectedExceptionMessage An "Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\CompositeKeyEntity" entity with "array(id = 123, title = test)" identifier does not exist.
     */
    // @codingStandardsIgnoreEnd
    public function testReverseTransformWhenEntityWithCompositeKeyNotFound()
    {
        $metadata = $this->getAssociationMetadata([CompositeKeyEntity::class]);
        $transformer = $this->getNestedAssociationTransformer($metadata);

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

    /**
     * @expectedException \Symfony\Component\Form\Exception\TransformationFailedException
     * @expectedExceptionMessage Expected an array.
     */
    public function testReverseTransformWhenInvalidValueType()
    {
        $metadata = $this->getAssociationMetadata([Group::class]);
        $transformer = $this->getNestedAssociationTransformer($metadata);
        $transformer->reverseTransform(123);
    }

    /**
     * @expectedException \Symfony\Component\Form\Exception\TransformationFailedException
     * @expectedExceptionMessage Expected an array with "class" element.
     */
    public function testReverseTransformWhenValueDoesNotHaveClass()
    {
        $metadata = $this->getAssociationMetadata([Group::class]);
        $transformer = $this->getNestedAssociationTransformer($metadata);
        $transformer->reverseTransform(['id' => 123]);
    }

    /**
     * @expectedException \Symfony\Component\Form\Exception\TransformationFailedException
     * @expectedExceptionMessage Expected an array with "id" element.
     */
    public function testReverseTransformWhenValueDoesNotHaveId()
    {
        $metadata = $this->getAssociationMetadata([Group::class]);
        $transformer = $this->getNestedAssociationTransformer($metadata);
        $transformer->reverseTransform(['class' => Group::class]);
    }

    /**
     * @expectedException \Symfony\Component\Form\Exception\TransformationFailedException
     * @expectedExceptionMessage There are no acceptable classes.
     */
    public function testReverseTransformWhenAnyEntityTypeShouldBeRejected()
    {
        $metadata = $this->getAssociationMetadata([]);
        $metadata->setEmptyAcceptableTargetsAllowed(false);
        $transformer = $this->getNestedAssociationTransformer($metadata);
        $transformer->reverseTransform(['class' => Group::class, 'id' => 123]);
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Symfony\Component\Form\Exception\TransformationFailedException
     * @expectedExceptionMessage The "Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User" class is not acceptable. Acceptable classes: Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group.
     */
    // @codingStandardsIgnoreEnd
    public function testReverseTransformForNotAcceptableEntity()
    {
        $metadata = $this->getAssociationMetadata([Group::class]);
        $transformer = $this->getNestedAssociationTransformer($metadata);

        $transformer->reverseTransform(['class' => User::class, 'id' => 123]);
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Symfony\Component\Form\Exception\TransformationFailedException
     * @expectedExceptionMessage The "Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group" class must be a managed Doctrine entity.
     */
    // @codingStandardsIgnoreEnd
    public function testReverseTransformForNotManageableEntity()
    {
        $metadata = $this->getAssociationMetadata([Group::class]);
        $transformer = $this->getNestedAssociationTransformer($metadata);

        $this->notManageableClassNames = [Group::class];
        $transformer->reverseTransform(['class' => Group::class, 'id' => 123]);
    }

    public function testReverseTransformWhenAnyEntityTypeIsAcceptable()
    {
        $metadata = $this->getAssociationMetadata([]);
        $transformer = $this->getNestedAssociationTransformer($metadata);

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

        self::assertEquals(
            new EntityIdentifier($entity->getId(), get_class($entity)),
            $transformer->reverseTransform($value)
        );
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Symfony\Component\Form\Exception\TransformationFailedException
     * @expectedExceptionMessage An "Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group" entity with "array(primary = 1)" identifier cannot be loaded.
     */
    // @codingStandardsIgnoreEnd
    public function testReverseTransformWhenDoctrineIsNotAbleToLoadEntity()
    {
        $metadata = $this->getAssociationMetadata([Group::class]);
        $transformer = $this->getNestedAssociationTransformer($metadata);

        $value = ['class' => Group::class, 'id' => ['primary' => 1]];

        $transformer->reverseTransform($value);
    }
}
