<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Util;

use Doctrine\ORM\Query\Parameter;
use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\FieldMetadata;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\CompositeKeyEntity;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User;
use Oro\Bundle\ApiBundle\Tests\Unit\OrmRelatedTestCase;
use Oro\Bundle\ApiBundle\Util\EntityIdHelper;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class EntityIdHelperTest extends OrmRelatedTestCase
{
    private EntityIdHelper $entityIdHelper;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->entityIdHelper = new EntityIdHelper();
    }

    public function testSetIdentifierForEntityWithSingleId(): void
    {
        $entityId = 123;
        $entity = $this->createMock(Entity\Group::class);
        $entityMetadata = new EntityMetadata(Entity\Group::class);
        $entityMetadata->setIdentifierFieldNames(['id']);
        $entityMetadata->addField(new FieldMetadata('id'));

        $entity->expects(self::once())
            ->method('setId')
            ->with(self::identicalTo($entityId));

        $this->entityIdHelper->setEntityIdentifier($entity, $entityId, $entityMetadata);
    }

    public function testSetIdentifierForEntityWithSingleIdWithoutSetter(): void
    {
        $entityId = 123;
        $entity = new Entity\EntityWithoutGettersAndSetters();
        $entityMetadata = new EntityMetadata(Entity\EntityWithoutGettersAndSetters::class);
        $entityMetadata->setIdentifierFieldNames(['id']);
        $entityMetadata->addField(new FieldMetadata('id'));

        $this->entityIdHelper->setEntityIdentifier($entity, $entityId, $entityMetadata);
        self::assertEquals($entityId, $entity->id);
    }

    public function testSetIdentifierForEntityWithCompositeId(): void
    {
        $entityId = ['id' => 123, 'title' => 'test'];
        $entity = $this->createMock(Entity\CompositeKeyEntity::class);
        $entityMetadata = new EntityMetadata(Entity\CompositeKeyEntity::class);
        $entityMetadata->setIdentifierFieldNames(['id', 'title']);
        $entityMetadata->addField(new FieldMetadata('id'));
        $entityMetadata->addField(new FieldMetadata('title'));

        $entity->expects(self::once())
            ->method('setId')
            ->with(self::identicalTo($entityId['id']));
        $entity->expects(self::once())
            ->method('setTitle')
            ->with(self::identicalTo($entityId['title']));

        $this->entityIdHelper->setEntityIdentifier($entity, $entityId, $entityMetadata);
    }

    public function testSetIdentifierForEntityWithCompositeIdWithRenamedIdentifierFields(): void
    {
        $entityId = ['renamedId' => 123, 'renamedTitle' => 'test'];
        $entity = new Entity\CompositeKeyEntity();
        $entityMetadata = new EntityMetadata(Entity\CompositeKeyEntity::class);
        $entityMetadata->setIdentifierFieldNames(['id', 'title']);
        $entityMetadata->addField(new FieldMetadata('renamedId'))->setPropertyPath('id');
        $entityMetadata->addField(new FieldMetadata('renamedTitle'))->setPropertyPath('title');

        $this->entityIdHelper->setEntityIdentifier($entity, $entityId, $entityMetadata);
        self::assertEquals($entityId['renamedId'], $entity->getId());
        self::assertEquals($entityId['renamedTitle'], $entity->getTitle());
    }

    public function testSetInvalidIdentifierForEntityWithCompositeId(): void
    {
        $entityId = 123;
        $entity = new Entity\CompositeKeyEntity();
        $entityMetadata = new EntityMetadata(Entity\CompositeKeyEntity::class);
        $entityMetadata->setIdentifierFieldNames(['id', 'title']);
        $entityMetadata->addField(new FieldMetadata('id'));
        $entityMetadata->addField(new FieldMetadata('title'));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(
            'Unexpected identifier value "%s" for composite identifier of the entity "%s".',
            $entityId,
            Entity\CompositeKeyEntity::class
        ));

        $this->entityIdHelper->setEntityIdentifier($entity, $entityId, $entityMetadata);
    }

    public function testSetIdentifierWithUndefinedFieldMetadataForEntityWithCompositeId(): void
    {
        $entityId = ['id' => 123, 'title1' => 'test'];
        $entity = new Entity\CompositeKeyEntity();
        $entityMetadata = new EntityMetadata(Entity\CompositeKeyEntity::class);
        $entityMetadata->setIdentifierFieldNames(['id', 'title']);
        $entityMetadata->addField(new FieldMetadata('id'));
        $entityMetadata->addField(new FieldMetadata('title'));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(
            'The entity "%s" does not have metadata for the "title1" property.',
            Entity\CompositeKeyEntity::class
        ));

        $this->entityIdHelper->setEntityIdentifier($entity, $entityId, $entityMetadata);
    }

    public function testSetIdentifierWithUndefinedFieldForEntityWithCompositeId(): void
    {
        $entityId = ['id' => 123, 'title1' => 'test'];
        $entity = new Entity\CompositeKeyEntity();
        $entityMetadata = new EntityMetadata(Entity\CompositeKeyEntity::class);
        $entityMetadata->setIdentifierFieldNames(['id', 'title']);
        $entityMetadata->addField(new FieldMetadata('id'));
        $entityMetadata->addField(new FieldMetadata('title1'));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(
            'The entity "%s" does not have the "title1" property.',
            Entity\CompositeKeyEntity::class
        ));

        $this->entityIdHelper->setEntityIdentifier($entity, $entityId, $entityMetadata);
    }

    public function testSetIdentifierForEnumEntity(): void
    {
        /** @var EnumOption $entity */
        $entity = (new \ReflectionClass(EnumOption::class))->newInstanceWithoutConstructor();
        $entityMetadata = new EntityMetadata('Extend\Entity\EV_TestEnum');
        $entityMetadata->setIdentifierFieldNames(['id']);
        $entityMetadata->addField(new FieldMetadata('id'));

        $this->entityIdHelper->setEntityIdentifier($entity, 'testenum.item', $entityMetadata);
        self::assertEquals('testenum.item', $entity->getId());
        self::assertEquals('testenum', $entity->getEnumCode());
        self::assertEquals('item', $entity->getInternalId());
    }

    public function testApplyEntityIdentifierRestrictionForSingleIdEntity(): void
    {
        $entityClass = Entity\User::class;
        $entityId = 123;
        $entityMetadata = new EntityMetadata($entityClass);
        $entityMetadata->setIdentifierFieldNames(['id']);
        $entityMetadata->addField(new FieldMetadata('id'));

        $qb = $this->em->createQueryBuilder();
        $qb->from($entityClass, 'e')->select('e');

        $this->entityIdHelper->applyEntityIdentifierRestriction($qb, $entityId, $entityMetadata);

        self::assertEquals(
            sprintf('SELECT e FROM %s e WHERE e.id = :id', $entityClass),
            $qb->getDQL()
        );
        /** @var Parameter $parameter */
        $parameter = $qb->getParameters()->first();
        self::assertEquals('id', $parameter->getName());
        self::assertEquals($entityId, $parameter->getValue());
    }

    public function testApplyEntityIdentifierRestrictionForSingleIdEntityWithRenamedIdentifierField(): void
    {
        $entityClass = Entity\User::class;
        $entityId = 123;
        $entityMetadata = new EntityMetadata($entityClass);
        $entityMetadata->setIdentifierFieldNames(['renamedId']);
        $entityMetadata->addField(new FieldMetadata('renamedId'))->setPropertyPath('id');

        $qb = $this->em->createQueryBuilder();
        $qb->from($entityClass, 'e')->select('e');

        $this->entityIdHelper->applyEntityIdentifierRestriction($qb, $entityId, $entityMetadata);

        self::assertEquals(
            sprintf('SELECT e FROM %s e WHERE e.id = :id', $entityClass),
            $qb->getDQL()
        );
        /** @var Parameter $parameter */
        $parameter = $qb->getParameters()->first();
        self::assertEquals('id', $parameter->getName());
        self::assertEquals($entityId, $parameter->getValue());
    }

    public function testApplyEntityIdentifierRestrictionForSingleIdEntityWithArrayId(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(sprintf(
            'The entity identifier cannot be an array because the entity "%s" has single identifier.',
            User::class
        ));

        $entityClass = Entity\User::class;
        $entityId = [1, 2];
        $entityMetadata = new EntityMetadata($entityClass);
        $entityMetadata->setIdentifierFieldNames(['id']);
        $entityMetadata->addField(new FieldMetadata('id'));

        $qb = $this->em->createQueryBuilder();
        $qb->from($entityClass, 'e')->select('e');

        $this->entityIdHelper->applyEntityIdentifierRestriction($qb, $entityId, $entityMetadata);
    }

    public function testApplyEntityIdentifierRestrictionForCompositeIdEntity(): void
    {
        $entityClass = Entity\CompositeKeyEntity::class;
        $entityId = ['id' => 123, 'title' => 'test'];
        $entityMetadata = new EntityMetadata($entityClass);
        $entityMetadata->setIdentifierFieldNames(['id', 'title']);
        $entityMetadata->addField(new FieldMetadata('id'));
        $entityMetadata->addField(new FieldMetadata('title'));

        $qb = $this->em->createQueryBuilder();
        $qb->from($entityClass, 'e')->select('e');

        $this->entityIdHelper->applyEntityIdentifierRestriction($qb, $entityId, $entityMetadata);

        self::assertEquals(
            sprintf('SELECT e FROM %s e WHERE e.id = :id1 AND e.title = :id2', $entityClass),
            $qb->getDQL()
        );
        /** @var Parameter $parameter */
        $parameters = $qb->getParameters();
        $idParameter = $parameters[0];
        self::assertEquals('id1', $idParameter->getName());
        self::assertEquals($entityId['id'], $idParameter->getValue());
        $titleParameter = $parameters[1];
        self::assertEquals('id2', $titleParameter->getName());
        self::assertEquals($entityId['title'], $titleParameter->getValue());
    }

    public function testApplyEntityIdentifierRestrictionForCompositeIdEntityWithRenamedIdentifierFields(): void
    {
        $entityClass = Entity\CompositeKeyEntity::class;
        $entityId = ['renamedId' => 123, 'renamedTitle' => 'test'];
        $entityMetadata = new EntityMetadata($entityClass);
        $entityMetadata->setIdentifierFieldNames(['renamedId', 'renamedTitle']);
        $entityMetadata->addField(new FieldMetadata('renamedId'))->setPropertyPath('id');
        $entityMetadata->addField(new FieldMetadata('renamedTitle'))->setPropertyPath('title');

        $qb = $this->em->createQueryBuilder();
        $qb->from($entityClass, 'e')->select('e');

        $this->entityIdHelper->applyEntityIdentifierRestriction($qb, $entityId, $entityMetadata);

        self::assertEquals(
            sprintf('SELECT e FROM %s e WHERE e.id = :id1 AND e.title = :id2', $entityClass),
            $qb->getDQL()
        );
        /** @var Parameter $parameter */
        $parameters = $qb->getParameters();
        $idParameter = $parameters[0];
        self::assertEquals('id1', $idParameter->getName());
        self::assertEquals($entityId['renamedId'], $idParameter->getValue());
        $titleParameter = $parameters[1];
        self::assertEquals('id2', $titleParameter->getName());
        self::assertEquals($entityId['renamedTitle'], $titleParameter->getValue());
    }

    public function testApplyEntityIdentifierRestrictionForCompositeIdEntityWithScalarId(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(sprintf(
            'The entity identifier must be an array because the entity "%s" has composite identifier.',
            CompositeKeyEntity::class
        ));

        $entityClass = Entity\CompositeKeyEntity::class;
        $entityId = 123;
        $entityMetadata = new EntityMetadata($entityClass);
        $entityMetadata->setIdentifierFieldNames(['id', 'title']);
        $entityMetadata->addField(new FieldMetadata('id'));
        $entityMetadata->addField(new FieldMetadata('title'));

        $qb = $this->em->createQueryBuilder();
        $qb->from($entityClass, 'e')->select('e');

        $this->entityIdHelper->applyEntityIdentifierRestriction($qb, $entityId, $entityMetadata);
    }

    public function testApplyEntityIdentifierRestrictionForCompositeIdEntityWithWrongId(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(sprintf(
            'The entity identifier array must have the key "title" because the entity "%s" has composite identifier.',
            CompositeKeyEntity::class
        ));

        $entityClass = Entity\CompositeKeyEntity::class;
        $entityId = ['id' => 123];
        $entityMetadata = new EntityMetadata($entityClass);
        $entityMetadata->setIdentifierFieldNames(['id', 'title']);
        $entityMetadata->addField(new FieldMetadata('id'));
        $entityMetadata->addField(new FieldMetadata('title'));

        $qb = $this->em->createQueryBuilder();
        $qb->from($entityClass, 'e')->select('e');

        $this->entityIdHelper->applyEntityIdentifierRestriction($qb, $entityId, $entityMetadata);
    }

    /**
     * @dataProvider isEntityIdentifierEmptyDataProvider
     */
    public function testIsEntityIdentifierEmpty(int|string|array|null $id, bool $expected): void
    {
        self::assertSame($expected, $this->entityIdHelper->isEntityIdentifierEmpty($id));
    }

    public function isEntityIdentifierEmptyDataProvider(): array
    {
        return [
            [null, true],
            [[], true],
            [['id1' => null], true],
            [['id1' => null, 'id2' => null], true],
            [0, false],
            [1, false],
            ['', false],
            ['test', false],
            [['id1' => 0], false],
            [['id1' => 1], false],
            [['id1' => ''], false],
            [['id1' => 'test'], false],
            [['id1' => null, 'id2' => 0], false],
            [['id1' => null, 'id2' => 1], false],
            [['id1' => null, 'id2' => ''], false],
            [['id1' => null, 'id2' => 'test'], false],
            [['id1' => 1, 'id2' => 2], false]
        ];
    }
}
