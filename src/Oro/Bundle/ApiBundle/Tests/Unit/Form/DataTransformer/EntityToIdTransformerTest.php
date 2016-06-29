<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Form\DateTransformer;

use Oro\Bundle\ApiBundle\Form\DataTransformer\EntityToIdTransformer;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group;
use Oro\Bundle\ApiBundle\Tests\Unit\OrmRelatedTestCase;

class EntityToIdTransformerTest extends OrmRelatedTestCase
{
    /** @var EntityToIdTransformer */
    protected $transformer;

    /** @var AssociationMetadata */
    protected $metadata;

    protected function setUp()
    {
        parent::setUp();

        $this->metadata = new AssociationMetadata();
        $this->metadata->setAcceptableTargetClassNames(
            ['Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group']
        );

        $this->transformer = new EntityToIdTransformer($this->doctrine, $this->metadata);
    }

    public function testTransform()
    {
        $this->assertNull($this->transformer->transform(new \stdClass()));
    }

    /**
     * @dataProvider reverseTransformForEmptyValueDataProvider
     */
    public function testReverseTransformForEmptyValue($value, $expected)
    {
        $this->assertEquals($expected, $this->transformer->reverseTransform($value));
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
        $value = ['class' => 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group', 'id' => 123];
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

        $this->assertEquals($entity, $this->transformer->reverseTransform($value));
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Symfony\Component\Form\Exception\TransformationFailedException
     * @expectedExceptionMessage An "Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group" entity with "123" identifier does not exist.
     */
    // @codingStandardsIgnoreEnd
    public function testReverseTransformWhenEntityNotFound()
    {
        $value = ['class' => 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group', 'id' => 123];

        $stmt = $this->createFetchStatementMock(
            [],
            [1 => $value['id']],
            [1 => \PDO::PARAM_INT]
        );
        $this->getDriverConnectionMock($this->em)->expects($this->any())
            ->method('prepare')
            ->willReturn($stmt);

        $this->transformer->reverseTransform($value);
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Symfony\Component\Form\Exception\TransformationFailedException
     * @expectedExceptionMessage An "Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\CompositeKeyEntity" entity with "array(id = 123, title = test)" identifier does not exist.
     */
    // @codingStandardsIgnoreEnd
    public function testReverseTransformWhenEntityWithCompositeKeyNotFound()
    {
        $this->metadata->setAcceptableTargetClassNames(
            ['Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\CompositeKeyEntity']
        );

        $value = [
            'class' => 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\CompositeKeyEntity',
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

        $this->transformer->reverseTransform($value);
    }

    /**
     * @expectedException \Symfony\Component\Form\Exception\TransformationFailedException
     * @expectedExceptionMessage Expected an array.
     */
    public function testReverseTransformWhenInvalidValueType()
    {
        $this->transformer->reverseTransform(123);
    }

    /**
     * @expectedException \Symfony\Component\Form\Exception\TransformationFailedException
     * @expectedExceptionMessage Expected an array with "class" element.
     */
    public function testReverseTransformWhenValueDoesNotHaveClass()
    {
        $this->transformer->reverseTransform(['id' => 123]);
    }

    /**
     * @expectedException \Symfony\Component\Form\Exception\TransformationFailedException
     * @expectedExceptionMessage Expected an array with "id" element.
     */
    public function testReverseTransformWhenValueDoesNotHaveId()
    {
        $this->transformer->reverseTransform(['class' => 'Test\Class']);
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Symfony\Component\Form\Exception\TransformationFailedException
     * @expectedExceptionMessage The "Test\Class" class is not acceptable. Acceptable classes: Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group.
     */
    // @codingStandardsIgnoreEnd
    public function testReverseTransformForNotAcceptableEntity()
    {
        $this->notManageableClassNames = ['Test\Class'];
        $this->transformer->reverseTransform(['class' => 'Test\Class', 'id' => 123]);
    }

    /**
     * @expectedException \Symfony\Component\Form\Exception\TransformationFailedException
     * @expectedExceptionMessage The "Test\Class" class must be a managed Doctrine entity.
     */
    public function testReverseTransformForNotManageableEntity()
    {
        $this->metadata->setAcceptableTargetClassNames(['Test\Class']);

        $this->notManageableClassNames = ['Test\Class'];
        $this->transformer->reverseTransform(['class' => 'Test\Class', 'id' => 123]);
    }
}
