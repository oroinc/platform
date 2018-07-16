<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\GetSubresource;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\FieldMetadata;
use Oro\Bundle\ApiBundle\Processor\Shared\LoadTitleMetaProperty;
use Oro\Bundle\ApiBundle\Processor\Subresource\GetSubresource\LoadExtendedAssociation;
use Oro\Bundle\ApiBundle\Provider\EntityTitleProvider;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\GetSubresourceProcessorOrmRelatedTestCase;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\EntityIdHelper;
use Oro\Bundle\EntityExtendBundle\Entity\Manager\AssociationManager;
use Oro\Component\EntitySerializer\EntitySerializer;

class LoadExtendedAssociationTest extends GetSubresourceProcessorOrmRelatedTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|EntitySerializer */
    private $entitySerializer;

    /** @var \PHPUnit\Framework\MockObject\MockObject|AssociationManager */
    private $associationManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityTitleProvider */
    private $entityTitleProvider;

    /** @var LoadExtendedAssociation */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->entitySerializer = $this->createMock(EntitySerializer::class);
        $this->associationManager = $this->createMock(AssociationManager::class);
        $this->entityTitleProvider = $this->createMock(EntityTitleProvider::class);

        $this->processor = new LoadExtendedAssociation(
            $this->entitySerializer,
            $this->doctrineHelper,
            new EntityIdHelper(),
            $this->associationManager,
            $this->entityTitleProvider
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

    public function testProcessForExtendedManyToOneAssociationWithoutTitle()
    {
        $associationName = 'testAssociation';
        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->addField($associationName)->setDataType('association:manyToOne:kind');
        $parentMetadata = new EntityMetadata();
        $parentMetadata->setIdentifierFieldNames(['id']);
        $parentMetadata->addField(new FieldMetadata('id'));
        $parentClassName = Entity\Product::class;
        $parentId = 123;
        $config = new EntityDefinitionConfig();

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

        $this->context->setAssociationName($associationName);
        $this->context->setParentConfig($parentConfig);
        $this->context->setParentMetadata($parentMetadata);
        $this->context->setParentClassName($parentClassName);
        $this->context->setParentId($parentId);
        $this->context->setConfig($config);
        $this->processor->process($this->context);

        self::assertEquals(
            [
                'id'                   => 1,
                ConfigUtil::CLASS_NAME => Entity\User::class
            ],
            $this->context->getResult()
        );
        self::assertEquals(['normalize_data'], $this->context->getSkippedGroups());
        self::assertTrue($this->context->isProcessed(LoadTitleMetaProperty::OPERATION_NAME));
    }

    public function testProcessForExtendedManyToOneAssociationWithTitle()
    {
        $associationName = 'testAssociation';
        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->setIdentifierFieldNames(['id']);
        $parentConfig->addField($associationName)->setDataType('association:manyToOne:kind');
        $parentMetadata = new EntityMetadata();
        $parentMetadata->setIdentifierFieldNames(['id']);
        $parentMetadata->addField(new FieldMetadata('id'));
        $parentClassName = Entity\Product::class;
        $parentId = 123;
        $config = new EntityDefinitionConfig();
        $config->addField('__title__')->setMetaProperty(true);

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
        $this->entityTitleProvider->expects(self::once())
            ->method('getTitles')
            ->with([Entity\User::class => ['id', [1]]])
            ->willReturn([
                ['id' => 1, 'entity' => Entity\User::class, 'title' => 'test user']
            ]);

        $this->context->setAssociationName($associationName);
        $this->context->setParentConfig($parentConfig);
        $this->context->setParentMetadata($parentMetadata);
        $this->context->setParentClassName($parentClassName);
        $this->context->setParentId($parentId);
        $this->context->setConfig($config);
        $this->processor->process($this->context);

        self::assertEquals(
            [
                'id'                   => 1,
                ConfigUtil::CLASS_NAME => Entity\User::class,
                '__title__'            => 'test user'
            ],
            $this->context->getResult()
        );
        self::assertEquals(['normalize_data'], $this->context->getSkippedGroups());
        self::assertTrue($this->context->isProcessed(LoadTitleMetaProperty::OPERATION_NAME));
    }

    public function testProcessForExtendedManyToOneAssociationWhenTitleAlreadyLoaded()
    {
        $associationName = 'testAssociation';
        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->addField($associationName)->setDataType('association:manyToOne:kind');
        $parentMetadata = new EntityMetadata();
        $parentMetadata->setIdentifierFieldNames(['id']);
        $parentMetadata->addField(new FieldMetadata('id'));
        $parentClassName = Entity\Product::class;
        $parentId = 123;
        $config = new EntityDefinitionConfig();
        $config->addField('__title__')->setMetaProperty(true);

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

        $this->context->setAssociationName($associationName);
        $this->context->setParentConfig($parentConfig);
        $this->context->setParentMetadata($parentMetadata);
        $this->context->setParentClassName($parentClassName);
        $this->context->setParentId($parentId);
        $this->context->setConfig($config);
        $this->context->setProcessed(LoadTitleMetaProperty::OPERATION_NAME);
        $this->processor->process($this->context);

        self::assertEquals(
            [
                'id'                   => 1,
                ConfigUtil::CLASS_NAME => Entity\User::class
            ],
            $this->context->getResult()
        );
        self::assertEquals(['normalize_data'], $this->context->getSkippedGroups());
        self::assertTrue($this->context->isProcessed(LoadTitleMetaProperty::OPERATION_NAME));
    }

    public function testProcessForEmptyResultOfManyToOneExtendedAssociation()
    {
        $associationName = 'testAssociation';
        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->addField($associationName)->setDataType('association:manyToOne:kind');
        $parentMetadata = new EntityMetadata();
        $parentMetadata->setIdentifierFieldNames(['id']);
        $parentMetadata->addField(new FieldMetadata('id'));
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
        $this->context->setParentMetadata($parentMetadata);
        $this->context->setParentClassName($parentClassName);
        $this->context->setParentId($parentId);
        $this->processor->process($this->context);

        self::assertNull($this->context->getResult());
        self::assertEquals(['normalize_data'], $this->context->getSkippedGroups());
        self::assertTrue($this->context->isProcessed(LoadTitleMetaProperty::OPERATION_NAME));
    }

    public function testProcessForExtendedManyToManyAssociationWithTitle()
    {
        $associationName = 'testAssociation';
        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->setIdentifierFieldNames(['id']);
        $parentConfig->addField($associationName)->setDataType('association:manyToMany:kind');
        $parentMetadata = new EntityMetadata();
        $parentMetadata->setIdentifierFieldNames(['id']);
        $parentMetadata->addField(new FieldMetadata('id'));
        $parentClassName = Entity\Product::class;
        $parentId = 123;
        $config = new EntityDefinitionConfig();
        $config->addField('__title__')->setMetaProperty(true);

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
        $this->entityTitleProvider->expects(self::once())
            ->method('getTitles')
            ->with([Entity\User::class => ['id', [1]]])
            ->willReturn([
                ['id' => 1, 'entity' => Entity\User::class, 'title' => 'test user']
            ]);

        $this->context->setAssociationName($associationName);
        $this->context->setParentConfig($parentConfig);
        $this->context->setParentMetadata($parentMetadata);
        $this->context->setParentClassName($parentClassName);
        $this->context->setParentId($parentId);
        $this->context->setConfig($config);
        $this->processor->process($this->context);

        self::assertEquals(
            [
                [
                    'id'                   => 1,
                    ConfigUtil::CLASS_NAME => Entity\User::class,
                    '__title__'            => 'test user'
                ]
            ],
            $this->context->getResult()
        );
        self::assertEquals(['normalize_data'], $this->context->getSkippedGroups());
        self::assertTrue($this->context->isProcessed(LoadTitleMetaProperty::OPERATION_NAME));
    }

    public function testProcessForEmptyResultOfManyToManyExtendedAssociation()
    {
        $associationName = 'testAssociation';
        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->addField($associationName)->setDataType('association:manyToMany:kind');
        $parentMetadata = new EntityMetadata();
        $parentMetadata->setIdentifierFieldNames(['id']);
        $parentMetadata->addField(new FieldMetadata('id'));
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
        $this->context->setParentMetadata($parentMetadata);
        $this->context->setParentClassName($parentClassName);
        $this->context->setParentId($parentId);
        $this->processor->process($this->context);

        self::assertSame([], $this->context->getResult());
        self::assertEquals(['normalize_data'], $this->context->getSkippedGroups());
        self::assertTrue($this->context->isProcessed(LoadTitleMetaProperty::OPERATION_NAME));
    }

    public function testProcessForEmptyResultOfMultipleManyToOneExtendedAssociation()
    {
        $associationName = 'testAssociation';
        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->addField($associationName)->setDataType('association:multipleManyToOne:kind');
        $parentMetadata = new EntityMetadata();
        $parentMetadata->setIdentifierFieldNames(['id']);
        $parentMetadata->addField(new FieldMetadata('id'));
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
        $this->context->setParentMetadata($parentMetadata);
        $this->context->setParentClassName($parentClassName);
        $this->context->setParentId($parentId);
        $this->processor->process($this->context);

        self::assertSame([], $this->context->getResult());
        self::assertEquals(['normalize_data'], $this->context->getSkippedGroups());
        self::assertTrue($this->context->isProcessed(LoadTitleMetaProperty::OPERATION_NAME));
    }

    public function testProcessForManyToOneExtendedAssociationWhenParentEntityWasNotFound()
    {
        $associationName = 'testAssociation';
        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->addField($associationName)->setDataType('association:manyToOne:kind');
        $parentMetadata = new EntityMetadata();
        $parentMetadata->setIdentifierFieldNames(['id']);
        $parentMetadata->addField(new FieldMetadata('id'));
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
        $this->context->setParentMetadata($parentMetadata);
        $this->context->setParentClassName($parentClassName);
        $this->context->setParentId($parentId);
        $this->processor->process($this->context);

        self::assertNull($this->context->getResult());
        self::assertEquals(['normalize_data'], $this->context->getSkippedGroups());
        self::assertTrue($this->context->isProcessed(LoadTitleMetaProperty::OPERATION_NAME));
    }

    public function testProcessForManyToManyExtendedAssociationWhenParentEntityWasNotFound()
    {
        $associationName = 'testAssociation';
        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->addField($associationName)->setDataType('association:manyToMany:kind');
        $parentMetadata = new EntityMetadata();
        $parentMetadata->setIdentifierFieldNames(['id']);
        $parentMetadata->addField(new FieldMetadata('id'));
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
        $this->context->setParentMetadata($parentMetadata);
        $this->context->setParentClassName($parentClassName);
        $this->context->setParentId($parentId);
        $this->processor->process($this->context);

        self::assertSame([], $this->context->getResult());
        self::assertEquals(['normalize_data'], $this->context->getSkippedGroups());
        self::assertTrue($this->context->isProcessed(LoadTitleMetaProperty::OPERATION_NAME));
    }

    public function testProcessForMultipleManyToOneExtendedAssociationWhenParentEntityWasNotFound()
    {
        $associationName = 'testAssociation';
        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->addField($associationName)->setDataType('association:multipleManyToOne:kind');
        $parentMetadata = new EntityMetadata();
        $parentMetadata->setIdentifierFieldNames(['id']);
        $parentMetadata->addField(new FieldMetadata('id'));
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
        $this->context->setParentMetadata($parentMetadata);
        $this->context->setParentClassName($parentClassName);
        $this->context->setParentId($parentId);
        $this->processor->process($this->context);

        self::assertSame([], $this->context->getResult());
        self::assertEquals(['normalize_data'], $this->context->getSkippedGroups());
        self::assertTrue($this->context->isProcessed(LoadTitleMetaProperty::OPERATION_NAME));
    }
}
