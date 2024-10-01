<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Util;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity;
use Oro\Bundle\ApiBundle\Tests\Unit\OrmRelatedTestCase;
use Oro\Bundle\EntityBundle\Exception\NotManageableEntityException;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class DoctrineHelperTest extends OrmRelatedTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->notManageableClassNames = [\stdClass::class];
    }

    private function getClassMetadata(string $entityClass): ClassMetadata
    {
        return $this->doctrineHelper->getEntityMetadataForClass($entityClass);
    }

    public function testIsManageableEntityClassForEnumOptionEntity(): void
    {
        self::assertFalse($this->doctrineHelper->isManageableEntityClass('Extend\Entity\EV_Test_Enum'));
    }

    public function testIsManageableEntityClassForManageableEntity(): void
    {
        self::assertTrue($this->doctrineHelper->isManageableEntityClass(Entity\Category::class));
    }

    public function testIsManageableEntityClassForNotManageableEntity(): void
    {
        self::assertFalse($this->doctrineHelper->isManageableEntityClass(\stdClass::class));
    }

    public function testGetEntityManagerForClassForEnumOptionEntity(): void
    {
        $this->expectException(NotManageableEntityException::class);
        $this->expectExceptionMessage('Entity class "Extend\Entity\EV_Test_Enum" is not manageable.');

        $this->doctrineHelper->getEntityManagerForClass('Extend\Entity\EV_Test_Enum');
    }

    public function testGetEntityManagerForClassForEnumOptionEntityAndWithoutThrowException(): void
    {
        self::assertNull($this->doctrineHelper->getEntityManagerForClass('Extend\Entity\EV_Test_Enum', false));
    }

    public function testGetEntityManagerForClassForManageableEntity(): void
    {
        self::assertSame($this->em, $this->doctrineHelper->getEntityManagerForClass(Entity\Category::class));
    }

    public function testGetEntityManagerForClassForNotManageableEntity(): void
    {
        $this->expectException(NotManageableEntityException::class);
        $this->expectExceptionMessage('Entity class "stdClass" is not manageable.');

        $this->doctrineHelper->getEntityManagerForClass(\stdClass::class);
    }

    public function testGetEntityManagerForClassForNotManageableEntityAndWithoutThrowException(): void
    {
        self::assertNull($this->doctrineHelper->getEntityManagerForClass(\stdClass::class, false));
    }

    public function testFindEntityMetadataByPathForAssociation()
    {
        self::assertEquals(
            $this->getClassMetadata(Entity\Category::class),
            $this->doctrineHelper->findEntityMetadataByPath(
                Entity\User::class,
                ['category']
            )
        );
    }

    public function testFindEntityMetadataByPathForField()
    {
        self::assertNull(
            $this->doctrineHelper->findEntityMetadataByPath(
                Entity\User::class,
                ['name']
            )
        );
    }

    public function testFindEntityMetadataByPathForStringPath()
    {
        self::assertEquals(
            $this->getClassMetadata(Entity\Category::class),
            $this->doctrineHelper->findEntityMetadataByPath(
                Entity\User::class,
                'products.category'
            )
        );
    }

    public function testFindEntityMetadataByPathForArrayPath()
    {
        self::assertEquals(
            $this->getClassMetadata(Entity\Category::class),
            $this->doctrineHelper->findEntityMetadataByPath(
                Entity\User::class,
                ['products', 'category']
            )
        );
    }

    public function testFindEntityMetadataByPathForDeepPath()
    {
        self::assertNull(
            $this->doctrineHelper->findEntityMetadataByPath(
                Entity\User::class,
                ['products', 'category', 'name']
            )
        );
    }

    public function testFindEntityMetadataByPathForNotManageableEntity()
    {
        $className = 'Test\Class';

        $this->notManageableClassNames = [$className];

        self::assertNull(
            $this->doctrineHelper->findEntityMetadataByPath($className, ['association'])
        );
    }

    public function testGetIndexedFields()
    {
        self::assertEquals(
            [
                'id'          => 'integer', // primary key
                'name'        => 'string', // unique constraint
                'description' => 'string' // index
            ],
            $this->doctrineHelper->getIndexedFields($this->getClassMetadata(Entity\Role::class))
        );
    }

    public function testGetIndexedAssociations()
    {
        self::assertEquals(
            [
                'category' => 'string', // many-to-one
                'owner'    => 'integer', // many-to-one
                'groups'   => 'integer', // many-to-many
                'products' => 'integer', // one-to-many
            ],
            $this->doctrineHelper->getIndexedAssociations($this->getClassMetadata(Entity\User::class))
        );
    }

    public function testGetFieldDataTypeForScalarField()
    {
        $metadata = $this->getClassMetadata(Entity\Role::class);

        self::assertEquals(
            'boolean',
            $this->doctrineHelper->getFieldDataType($metadata, 'enabled')
        );
    }

    public function testGetFieldDataTypeForUnknownField()
    {
        $metadata = $this->getClassMetadata(Entity\Role::class);

        self::assertNull(
            $this->doctrineHelper->getFieldDataType($metadata, 'unknown')
        );
    }

    public function testGetFieldDataTypeForAssociation()
    {
        $metadata = $this->getClassMetadata(Entity\Role::class);

        self::assertEquals(
            'integer',
            $this->doctrineHelper->getFieldDataType($metadata, 'users')
        );
    }

    public function testGetFieldDataTypeForAssociationWithCompositeIdentifier()
    {
        $metadata = $this->getClassMetadata(Entity\CompositeKeyEntity::class);

        self::assertEquals(
            'string',
            $this->doctrineHelper->getFieldDataType($metadata, 'children')
        );
    }
}
