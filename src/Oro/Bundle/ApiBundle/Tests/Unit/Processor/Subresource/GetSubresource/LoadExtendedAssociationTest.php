<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\GetSubresource;

use Oro\Component\EntitySerializer\EntitySerializer;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Subresource\GetSubresource\LoadExtendedAssociation;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\GetSubresourceProcessorOrmRelatedTestCase;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\EntityExtendBundle\Entity\Manager\AssociationManager;

class LoadExtendedAssociationTest extends GetSubresourceProcessorOrmRelatedTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entitySerializer;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $associationManager;

    /** @var LoadExtendedAssociation */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->entitySerializer = $this->getMockBuilder(EntitySerializer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->associationManager = $this->getMockBuilder(AssociationManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new LoadExtendedAssociation(
            $this->entitySerializer,
            $this->doctrineHelper,
            $this->associationManager
        );
    }

    public function testProcessWhenResultAlreadyExists()
    {
        $result = ['test'];

        $this->context->setResult($result);
        $this->processor->process($this->context);
        self::assertSame($result, $this->context->getResult());
        self::assertCount(0, $this->context->getSkippedGroups());
    }

    public function testProcessWhenAssociationConfigDoesNotExist()
    {
        $associationName = 'testAssociation';
        $parentConfig = new EntityDefinitionConfig();

        $this->context->setAssociationName($associationName);
        $this->context->setParentConfig($parentConfig);
        $this->processor->process($this->context);
        self::assertFalse($this->context->hasResult());
        self::assertCount(0, $this->context->getSkippedGroups());
    }

    public function testProcessForNotExtendedAssociation()
    {
        $associationName = 'testAssociation';
        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->addField($associationName);

        $this->context->setAssociationName($associationName);
        $this->context->setParentConfig($parentConfig);
        $this->processor->process($this->context);
        self::assertFalse($this->context->hasResult());
        self::assertCount(0, $this->context->getSkippedGroups());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Unsupported type of extended association: invalidType.
     */
    public function testProcessForInvalidTypeOfExtendedAssociation()
    {
        $associationName = 'testAssociation';
        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->addField($associationName)->setDataType('association:invalidType');

        $this->context->setAssociationName($associationName);
        $this->context->setParentConfig($parentConfig);
        $this->processor->process($this->context);
    }

    public function testProcessForExtendedManyToOneAssociation()
    {
        $associationName = 'testAssociation';
        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->addField($associationName)->setDataType('association:manyToOne:kind');
        $parentClassName = Entity\Product::class;
        $parentId = 123;
        $config = new EntityDefinitionConfig();
        $config->addField('title');

        $loadedData = [
            [
                'id'             => $parentId,
                $associationName => ['id' => 1, ConfigUtil::CLASS_NAME => Entity\User::class]
            ]
        ];

        $expectedQueryBuilder = $this->doctrineHelper->getEntityRepositoryForClass($parentClassName)
            ->createQueryBuilder('e')
            ->andWhere('e.id = :id')
            ->setParameter('id', $parentId);
        $this->entitySerializer->expects(self::once())
            ->method('serialize')
            ->with($expectedQueryBuilder, $parentConfig)
            ->willReturn($loadedData);
        $this->associationManager->expects(self::once())
            ->method('getAssociationTargets')
            ->with($parentClassName, null, 'manyToOne', 'kind')
            ->willReturn([Entity\User::class => 'owner', Entity\Role::class => 'role']);
        $this->associationManager->expects(self::once())
            ->method('getAssociationSubQueryBuilder')
            ->with($parentClassName, Entity\User::class, 'owner')
            ->willReturn(
                $this->em->getRepository($parentClassName)->createQueryBuilder('e')
                    ->select(
                        sprintf(
                            'e.id AS id, target.id AS entityId, \'%s\' AS entityClass, target.name AS entityTitle',
                            Entity\User::class
                        )
                    )
                    ->innerJoin('e.owner', 'target')
            );
        $sql = 'SELECT entity.id_1 AS id, entity.sclr_2 AS entity, entity.name_3 AS title '
            . 'FROM (('
            . 'SELECT p0_.id AS id_0, u1_.id AS id_1, '
            . '\'' . Entity\User::class . '\' AS sclr_2, u1_.name AS name_3 '
            . 'FROM product_table p0_ INNER JOIN user_table u1_ ON p0_.owner_id = u1_.id '
            . 'WHERE p0_.id = 123 AND u1_.id IN (1)'
            . ')) entity';
        $data = [
            ['id' => 1, 'entity' => Entity\User::class, 'title' => 'test user']
        ];
        $this->getDriverConnectionMock($this->em)->expects($this->once())
            ->method('query')
            ->with($sql)
            ->willReturn($this->createFetchStatementMock($data));

        $this->context->setAssociationName($associationName);
        $this->context->setParentConfig($parentConfig);
        $this->context->setParentClassName($parentClassName);
        $this->context->setParentId($parentId);
        $this->context->setConfig($config);
        $this->processor->process($this->context);
        self::assertEquals(
            [
                'id'                   => 1,
                ConfigUtil::CLASS_NAME => Entity\User::class,
                'title'                => 'test user'
            ],
            $this->context->getResult()
        );
        self::assertEquals(['normalize_data'], $this->context->getSkippedGroups());
    }

    public function testProcessForEmptyResultOfManyToOneExtendedAssociation()
    {
        $associationName = 'testAssociation';
        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->addField($associationName)->setDataType('association:manyToOne:kind');
        $parentClassName = Entity\Product::class;
        $parentId = 123;

        $loadedData = [
            ['id' => $parentId, $associationName => null]
        ];

        $expectedQueryBuilder = $this->doctrineHelper->getEntityRepositoryForClass($parentClassName)
            ->createQueryBuilder('e')
            ->andWhere('e.id = :id')
            ->setParameter('id', $parentId);
        $this->entitySerializer->expects(self::once())
            ->method('serialize')
            ->with($expectedQueryBuilder, $parentConfig)
            ->willReturn($loadedData);

        $this->context->setAssociationName($associationName);
        $this->context->setParentConfig($parentConfig);
        $this->context->setParentClassName($parentClassName);
        $this->context->setParentId($parentId);
        $this->processor->process($this->context);
        self::assertNull($this->context->getResult());
        self::assertEquals(['normalize_data'], $this->context->getSkippedGroups());
    }

    public function testProcessForExtendedManyToManyAssociation()
    {
        $associationName = 'testAssociation';
        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->addField($associationName)->setDataType('association:manyToMany:kind');
        $parentClassName = Entity\Product::class;
        $parentId = 123;
        $config = new EntityDefinitionConfig();
        $config->addField('title');

        $loadedData = [
            [
                'id'             => $parentId,
                $associationName => [['id' => 1, ConfigUtil::CLASS_NAME => Entity\User::class]]
            ]
        ];

        $expectedQueryBuilder = $this->doctrineHelper->getEntityRepositoryForClass($parentClassName)
            ->createQueryBuilder('e')
            ->andWhere('e.id = :id')
            ->setParameter('id', $parentId);
        $this->entitySerializer->expects(self::once())
            ->method('serialize')
            ->with($expectedQueryBuilder, $parentConfig)
            ->willReturn($loadedData);
        $this->associationManager->expects(self::once())
            ->method('getAssociationTargets')
            ->with($parentClassName, null, 'manyToMany', 'kind')
            ->willReturn([Entity\User::class => 'owner', Entity\Role::class => 'role']);
        $this->associationManager->expects(self::once())
            ->method('getAssociationSubQueryBuilder')
            ->with($parentClassName, Entity\User::class, 'owner')
            ->willReturn(
                $this->em->getRepository($parentClassName)->createQueryBuilder('e')
                    ->select(
                        sprintf(
                            'e.id AS id, target.id AS entityId, \'%s\' AS entityClass, target.name AS entityTitle',
                            Entity\User::class
                        )
                    )
                    ->innerJoin('e.owner', 'target')
            );
        $sql = 'SELECT entity.id_1 AS id, entity.sclr_2 AS entity, entity.name_3 AS title '
            . 'FROM (('
            . 'SELECT p0_.id AS id_0, u1_.id AS id_1, '
            . '\'' . Entity\User::class . '\' AS sclr_2, u1_.name AS name_3 '
            . 'FROM product_table p0_ INNER JOIN user_table u1_ ON p0_.owner_id = u1_.id '
            . 'WHERE p0_.id = 123 AND u1_.id IN (1)'
            . ')) entity';
        $data = [
            ['id' => 1, 'entity' => Entity\User::class, 'title' => 'test user']
        ];
        $this->getDriverConnectionMock($this->em)->expects($this->once())
            ->method('query')
            ->with($sql)
            ->willReturn($this->createFetchStatementMock($data));

        $this->context->setAssociationName($associationName);
        $this->context->setParentConfig($parentConfig);
        $this->context->setParentClassName($parentClassName);
        $this->context->setParentId($parentId);
        $this->context->setConfig($config);
        $this->processor->process($this->context);
        self::assertEquals(
            [
                [
                    'id'                   => 1,
                    ConfigUtil::CLASS_NAME => Entity\User::class,
                    'title'                => 'test user'
                ]
            ],
            $this->context->getResult()
        );
        self::assertEquals(['normalize_data'], $this->context->getSkippedGroups());
    }

    public function testProcessForEmptyResultOfManyToManyExtendedAssociation()
    {
        $associationName = 'testAssociation';
        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->addField($associationName)->setDataType('association:manyToMany:kind');
        $parentClassName = Entity\Product::class;
        $parentId = 123;

        $loadedData = [
            ['id' => $parentId, $associationName => []]
        ];

        $expectedQueryBuilder = $this->doctrineHelper->getEntityRepositoryForClass($parentClassName)
            ->createQueryBuilder('e')
            ->andWhere('e.id = :id')
            ->setParameter('id', $parentId);
        $this->entitySerializer->expects(self::once())
            ->method('serialize')
            ->with($expectedQueryBuilder, $parentConfig)
            ->willReturn($loadedData);

        $this->context->setAssociationName($associationName);
        $this->context->setParentConfig($parentConfig);
        $this->context->setParentClassName($parentClassName);
        $this->context->setParentId($parentId);
        $this->processor->process($this->context);
        self::assertSame([], $this->context->getResult());
        self::assertEquals(['normalize_data'], $this->context->getSkippedGroups());
    }

    public function testProcessForEmptyResultOfMultipleManyToOneExtendedAssociation()
    {
        $associationName = 'testAssociation';
        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->addField($associationName)->setDataType('association:multipleManyToOne:kind');
        $parentClassName = Entity\Product::class;
        $parentId = 123;

        $loadedData = [
            ['id' => $parentId, $associationName => []]
        ];

        $expectedQueryBuilder = $this->doctrineHelper->getEntityRepositoryForClass($parentClassName)
            ->createQueryBuilder('e')
            ->andWhere('e.id = :id')
            ->setParameter('id', $parentId);
        $this->entitySerializer->expects(self::once())
            ->method('serialize')
            ->with($expectedQueryBuilder, $parentConfig)
            ->willReturn($loadedData);

        $this->context->setAssociationName($associationName);
        $this->context->setParentConfig($parentConfig);
        $this->context->setParentClassName($parentClassName);
        $this->context->setParentId($parentId);
        $this->processor->process($this->context);
        self::assertSame([], $this->context->getResult());
        self::assertEquals(['normalize_data'], $this->context->getSkippedGroups());
    }

    public function testProcessForManyToOneExtendedAssociationWhenParentEntityWasNotFound()
    {
        $associationName = 'testAssociation';
        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->addField($associationName)->setDataType('association:manyToOne:kind');
        $parentClassName = Entity\Product::class;
        $parentId = 123;

        $loadedData = [];

        $expectedQueryBuilder = $this->doctrineHelper->getEntityRepositoryForClass($parentClassName)
            ->createQueryBuilder('e')
            ->andWhere('e.id = :id')
            ->setParameter('id', $parentId);
        $this->entitySerializer->expects(self::once())
            ->method('serialize')
            ->with($expectedQueryBuilder, $parentConfig)
            ->willReturn($loadedData);

        $this->context->setAssociationName($associationName);
        $this->context->setParentConfig($parentConfig);
        $this->context->setParentClassName($parentClassName);
        $this->context->setParentId($parentId);
        $this->processor->process($this->context);
        self::assertNull($this->context->getResult());
        self::assertEquals(['normalize_data'], $this->context->getSkippedGroups());
    }

    public function testProcessForManyToManyExtendedAssociationWhenParentEntityWasNotFound()
    {
        $associationName = 'testAssociation';
        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->addField($associationName)->setDataType('association:manyToMany:kind');
        $parentClassName = Entity\Product::class;
        $parentId = 123;

        $loadedData = [];

        $expectedQueryBuilder = $this->doctrineHelper->getEntityRepositoryForClass($parentClassName)
            ->createQueryBuilder('e')
            ->andWhere('e.id = :id')
            ->setParameter('id', $parentId);
        $this->entitySerializer->expects(self::once())
            ->method('serialize')
            ->with($expectedQueryBuilder, $parentConfig)
            ->willReturn($loadedData);

        $this->context->setAssociationName($associationName);
        $this->context->setParentConfig($parentConfig);
        $this->context->setParentClassName($parentClassName);
        $this->context->setParentId($parentId);
        $this->processor->process($this->context);
        self::assertSame([], $this->context->getResult());
        self::assertEquals(['normalize_data'], $this->context->getSkippedGroups());
    }

    public function testProcessForMultipleManyToOneExtendedAssociationWhenParentEntityWasNotFound()
    {
        $associationName = 'testAssociation';
        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->addField($associationName)->setDataType('association:multipleManyToOne:kind');
        $parentClassName = Entity\Product::class;
        $parentId = 123;

        $loadedData = [];

        $expectedQueryBuilder = $this->doctrineHelper->getEntityRepositoryForClass($parentClassName)
            ->createQueryBuilder('e')
            ->andWhere('e.id = :id')
            ->setParameter('id', $parentId);
        $this->entitySerializer->expects(self::once())
            ->method('serialize')
            ->with($expectedQueryBuilder, $parentConfig)
            ->willReturn($loadedData);

        $this->context->setAssociationName($associationName);
        $this->context->setParentConfig($parentConfig);
        $this->context->setParentClassName($parentClassName);
        $this->context->setParentId($parentId);
        $this->processor->process($this->context);
        self::assertSame([], $this->context->getResult());
        self::assertEquals(['normalize_data'], $this->context->getSkippedGroups());
    }
}
