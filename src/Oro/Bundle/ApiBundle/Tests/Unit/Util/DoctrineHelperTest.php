<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Util;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity;
use Oro\Bundle\ApiBundle\Tests\Unit\OrmRelatedTestCase;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

class DoctrineHelperTest extends OrmRelatedTestCase
{
    /**
     * @param string $entityClass
     *
     * @return ClassMetadata
     */
    protected function getClassMetadata($entityClass)
    {
        return $this->doctrineHelper->getEntityMetadataForClass($entityClass);
    }

    public function testIsManageableEntityClassShouldBeCached()
    {
        $entityClass = 'Test\Entity';
        $doctrine = $this->createMock('Doctrine\Common\Persistence\ManagerRegistry');
        $doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with($entityClass)
            ->willReturn($this->em);

        $doctrineHelper = new DoctrineHelper($doctrine);
        $this->assertTrue($doctrineHelper->isManageableEntityClass($entityClass));
        // test local cache
        $this->assertTrue($doctrineHelper->isManageableEntityClass($entityClass));
    }

    public function testIsManageableEntityClassShouldBeCachedEvenForNotManageableEntity()
    {
        $entityClass = 'Test\Entity';
        $doctrine = $this->createMock('Doctrine\Common\Persistence\ManagerRegistry');
        $doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with($entityClass)
            ->willReturn(null);

        $doctrineHelper = new DoctrineHelper($doctrine);
        $this->assertFalse($doctrineHelper->isManageableEntityClass($entityClass));
        // test local cache
        $this->assertFalse($doctrineHelper->isManageableEntityClass($entityClass));
    }

    public function testFindEntityMetadataByPathForAssociation()
    {
        $this->assertEquals(
            $this->getClassMetadata(Entity\Category::class),
            $this->doctrineHelper->findEntityMetadataByPath(
                Entity\User::class,
                ['category']
            )
        );
    }

    public function testFindEntityMetadataByPathForField()
    {
        $this->assertNull(
            $this->doctrineHelper->findEntityMetadataByPath(
                Entity\User::class,
                ['name']
            )
        );
    }

    public function testFindEntityMetadataByPathForStringPath()
    {
        $this->assertEquals(
            $this->getClassMetadata(Entity\Category::class),
            $this->doctrineHelper->findEntityMetadataByPath(
                Entity\User::class,
                'products.category'
            )
        );
    }

    public function testFindEntityMetadataByPathForArrayPath()
    {
        $this->assertEquals(
            $this->getClassMetadata(Entity\Category::class),
            $this->doctrineHelper->findEntityMetadataByPath(
                Entity\User::class,
                ['products', 'category']
            )
        );
    }

    public function testFindEntityMetadataByPathForDeepPath()
    {
        $this->assertNull(
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

        $this->assertNull(
            $this->doctrineHelper->findEntityMetadataByPath($className, ['association'])
        );
    }

    public function testGetOrderByIdentifierForEntityWithSingleIdentifier()
    {
        $this->assertEquals(
            ['id' => 'ASC'],
            $this->doctrineHelper->getOrderByIdentifier(Entity\User::class)
        );
        $this->assertEquals(
            ['id' => 'DESC'],
            $this->doctrineHelper->getOrderByIdentifier(Entity\User::class, true)
        );
    }

    public function testGetOrderByIdentifierForEntityWithCompositeIdentifier()
    {
        $this->assertEquals(
            ['id' => 'ASC', 'title' => 'ASC'],
            $this->doctrineHelper->getOrderByIdentifier(Entity\CompositeKeyEntity::class)
        );
        $this->assertEquals(
            ['id' => 'DESC', 'title' => 'DESC'],
            $this->doctrineHelper->getOrderByIdentifier(Entity\CompositeKeyEntity::class, true)
        );
    }

    public function testGetIndexedFields()
    {
        $this->assertEquals(
            [
                'id'          => 'integer', // primary key
                'name'        => 'string', // unique constraint
                'description' => 'string', // index
            ],
            $this->doctrineHelper->getIndexedFields($this->getClassMetadata(Entity\Role::class))
        );
    }

    public function testGetIndexedAssociations()
    {
        // category = ManyToOne
        // groups = ManyToMany (should be ignored)
        // products = OneToMany (should be ignored)
        // owner = ManyToOne
        $this->assertEquals(
            [
                'category' => 'string',
                'owner'    => 'integer',
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
