<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Form\DateTransformer;

use Oro\Bundle\ApiBundle\Collection\IncludedObjectCollection;
use Oro\Bundle\ApiBundle\Collection\IncludedObjectData;
use Oro\Bundle\ApiBundle\Form\DataTransformer\EntityToIdTransformer;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\CompositeKeyEntity;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group;
use Oro\Bundle\ApiBundle\Tests\Unit\OrmRelatedTestCase;

class EntityToIdTransformerTest extends OrmRelatedTestCase
{
    /**
     * @param string[] $acceptableTargetClassNames
     *
     * @return AssociationMetadata
     */
    protected function getAssociationMetadata(array $acceptableTargetClassNames = [])
    {
        $metadata = new AssociationMetadata();
        $metadata->setAcceptableTargetClassNames($acceptableTargetClassNames);

        return $metadata;
    }

    public function testTransform()
    {
        $metadata = $this->getAssociationMetadata([Group::class]);
        $transformer = new EntityToIdTransformer($this->doctrine, $metadata);
        $this->assertNull($transformer->transform(new \stdClass()));
    }

    /**
     * @dataProvider reverseTransformForEmptyValueDataProvider
     */
    public function testReverseTransformForEmptyValue($value, $expected)
    {
        $metadata = $this->getAssociationMetadata([Group::class]);
        $transformer = new EntityToIdTransformer($this->doctrine, $metadata);
        $this->assertEquals($expected, $transformer->reverseTransform($value));
    }

    public function reverseTransformForEmptyValueDataProvider()
    {
        return [
            [null, null],
            ['', null],
            [[], null],
        ];
    }

    public function testReverseTransform()
    {
        $metadata = $this->getAssociationMetadata([Group::class]);
        $transformer = new EntityToIdTransformer($this->doctrine, $metadata);

        $value = ['class' => Group::class, 'id' => 123];
        $entity = new Group();
        $entity->setId($value['id']);
        $entity->setName('test');

        $stmt = $this->createFetchStatementMock(
            [
                [
                    'id_1'   => $entity->getId(),
                    'name_2' => $entity->getName()
                ]
            ],
            [1 => $value['id']],
            [1 => \PDO::PARAM_INT]
        );
        $this->getDriverConnectionMock($this->em)->expects($this->any())
            ->method('prepare')
            ->with('SELECT t0.id AS id_1, t0.name AS name_2 FROM group_table t0 WHERE t0.id = ?')
            ->willReturn($stmt);

        $this->assertEquals($entity, $transformer->reverseTransform($value));
    }

    public function testReverseTransformWhenEntityDoesNotFoundInIncludedObject()
    {
        $metadata = $this->getAssociationMetadata([Group::class]);
        $includedObjects = new IncludedObjectCollection();
        $transformer = new EntityToIdTransformer($this->doctrine, $metadata, $includedObjects);

        $value = ['class' => Group::class, 'id' => 123];
        $entity = new Group();
        $entity->setId($value['id']);
        $entity->setName('test');

        $stmt = $this->createFetchStatementMock(
            [
                [
                    'id_1'   => $entity->getId(),
                    'name_2' => $entity->getName()
                ]
            ],
            [1 => $value['id']],
            [1 => \PDO::PARAM_INT]
        );
        $this->getDriverConnectionMock($this->em)->expects($this->any())
            ->method('prepare')
            ->with('SELECT t0.id AS id_1, t0.name AS name_2 FROM group_table t0 WHERE t0.id = ?')
            ->willReturn($stmt);

        $this->assertEquals($entity, $transformer->reverseTransform($value));
    }

    public function testReverseTransformWhenEntityFoundInIncludedObject()
    {
        $metadata = $this->getAssociationMetadata([Group::class]);
        $includedObjects = new IncludedObjectCollection();
        $transformer = new EntityToIdTransformer($this->doctrine, $metadata, $includedObjects);

        $value = ['class' => Group::class, 'id' => 123];
        $entity = new Group();
        $entity->setId($value['id']);
        $entity->setName('test');

        $includedObjects->add($entity, $value['class'], $value['id'], new IncludedObjectData('/included/0', 0));

        $this->assertEquals($entity, $transformer->reverseTransform($value));
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
        $transformer = new EntityToIdTransformer($this->doctrine, $metadata);

        $value = ['class' => Group::class, 'id' => 123];

        $stmt = $this->createFetchStatementMock(
            [],
            [1 => $value['id']],
            [1 => \PDO::PARAM_INT]
        );
        $this->getDriverConnectionMock($this->em)->expects($this->any())
            ->method('prepare')
            ->willReturn($stmt);

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
        $transformer = new EntityToIdTransformer($this->doctrine, $metadata);

        $value = [
            'class' => CompositeKeyEntity::class,
            'id'    => ['id' => 123, 'title' => 'test']
        ];

        $stmt = $this->createFetchStatementMock(
            [],
            [1 => $value['id']['id'], 2 => $value['id']['title']],
            [1 => \PDO::PARAM_INT, 2 => \PDO::PARAM_STR]
        );
        $this->getDriverConnectionMock($this->em)->expects($this->any())
            ->method('prepare')
            ->with(
                'SELECT t0.id AS id_1, t0.title AS title_2'
                . ' FROM composite_key_entity t0'
                . ' WHERE t0.id = ? AND t0.title = ?'
            )
            ->willReturn($stmt);

        $transformer->reverseTransform($value);
    }

    /**
     * @expectedException \Symfony\Component\Form\Exception\TransformationFailedException
     * @expectedExceptionMessage Expected an array.
     */
    public function testReverseTransformWhenInvalidValueType()
    {
        $metadata = $this->getAssociationMetadata([Group::class]);
        $transformer = new EntityToIdTransformer($this->doctrine, $metadata);
        $transformer->reverseTransform(123);
    }

    /**
     * @expectedException \Symfony\Component\Form\Exception\TransformationFailedException
     * @expectedExceptionMessage Expected an array with "class" element.
     */
    public function testReverseTransformWhenValueDoesNotHaveClass()
    {
        $metadata = $this->getAssociationMetadata([Group::class]);
        $transformer = new EntityToIdTransformer($this->doctrine, $metadata);
        $transformer->reverseTransform(['id' => 123]);
    }

    /**
     * @expectedException \Symfony\Component\Form\Exception\TransformationFailedException
     * @expectedExceptionMessage Expected an array with "id" element.
     */
    public function testReverseTransformWhenValueDoesNotHaveId()
    {
        $metadata = $this->getAssociationMetadata([Group::class]);
        $transformer = new EntityToIdTransformer($this->doctrine, $metadata);
        $transformer->reverseTransform(['class' => 'Test\Class']);
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Symfony\Component\Form\Exception\TransformationFailedException
     * @expectedExceptionMessage The "Test\Class" class is not acceptable. Acceptable classes: Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group.
     */
    // @codingStandardsIgnoreEnd
    public function testReverseTransformForNotAcceptableEntity()
    {
        $metadata = $this->getAssociationMetadata([Group::class]);
        $transformer = new EntityToIdTransformer($this->doctrine, $metadata);

        $this->notManageableClassNames = ['Test\Class'];
        $transformer->reverseTransform(['class' => 'Test\Class', 'id' => 123]);
    }

    /**
     * @expectedException \Symfony\Component\Form\Exception\TransformationFailedException
     * @expectedExceptionMessage The "Test\Class" class must be a managed Doctrine entity.
     */
    public function testReverseTransformForNotManageableEntity()
    {
        $metadata = $this->getAssociationMetadata(['Test\Class']);
        $transformer = new EntityToIdTransformer($this->doctrine, $metadata);

        $this->notManageableClassNames = ['Test\Class'];
        $transformer->reverseTransform(['class' => 'Test\Class', 'id' => 123]);
    }

    public function testReverseTransformWhenAnyEntityTypeIsAcceptable()
    {
        $metadata = $this->getAssociationMetadata([]);
        $transformer = new EntityToIdTransformer($this->doctrine, $metadata);

        $value = ['class' => Group::class, 'id' => 123];
        $entity = new Group();
        $entity->setId($value['id']);
        $entity->setName('test');

        $stmt = $this->createFetchStatementMock(
            [
                [
                    'id_1'   => $entity->getId(),
                    'name_2' => $entity->getName()
                ]
            ],
            [1 => $value['id']],
            [1 => \PDO::PARAM_INT]
        );
        $this->getDriverConnectionMock($this->em)->expects($this->any())
            ->method('prepare')
            ->with('SELECT t0.id AS id_1, t0.name AS name_2 FROM group_table t0 WHERE t0.id = ?')
            ->willReturn($stmt);

        $this->assertEquals($entity, $transformer->reverseTransform($value));
    }
}
