<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\GetSubresource;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\FieldMetadata;
use Oro\Bundle\ApiBundle\Processor\Shared\LoadTitleMetaProperty;
use Oro\Bundle\ApiBundle\Processor\Subresource\GetSubresource\LoadNestedAssociation;
use Oro\Bundle\ApiBundle\Provider\EntityTitleProvider;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\GetSubresourceProcessorOrmRelatedTestCase;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\EntityIdHelper;
use Oro\Component\EntitySerializer\EntitySerializer;

class LoadNestedAssociationTest extends GetSubresourceProcessorOrmRelatedTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|EntitySerializer */
    private $entitySerializer;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityTitleProvider */
    private $entityTitleProvider;

    /** @var LoadNestedAssociation */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->entitySerializer = $this->createMock(EntitySerializer::class);
        $this->entityTitleProvider = $this->createMock(EntityTitleProvider::class);

        $this->processor = new LoadNestedAssociation(
            $this->entitySerializer,
            $this->doctrineHelper,
            new EntityIdHelper(),
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

    public function testProcessForNotNestedAssociation()
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

    public function testProcessForNestedAssociationWithoutTitle()
    {
        $associationName = 'testAssociation';
        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->addField($associationName)->setDataType('nestedAssociation');
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

    public function testProcessForNestedAssociationWithTitle()
    {
        $associationName = 'testAssociation';
        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->addField($associationName)->setDataType('nestedAssociation');
        $parentMetadata = new EntityMetadata();
        $parentMetadata->setIdentifierFieldNames(['id']);
        $parentMetadata->addField(new FieldMetadata('id'));
        $parentClassName = Entity\Product::class;
        $parentId = 123;
        $config = new EntityDefinitionConfig();
        $config->setIdentifierFieldNames(['id']);
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
            ->willReturn([['id' => 1, 'entity' => Entity\User::class, 'title' => 'test user']]);

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

    public function testProcessForNestedAssociationWhenTitleWasNotFound()
    {
        $associationName = 'testAssociation';
        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->addField($associationName)->setDataType('nestedAssociation');
        $parentMetadata = new EntityMetadata();
        $parentMetadata->setIdentifierFieldNames(['id']);
        $parentMetadata->addField(new FieldMetadata('id'));
        $parentClassName = Entity\Product::class;
        $parentId = 123;
        $config = new EntityDefinitionConfig();
        $config->setIdentifierFieldNames(['id']);
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
            ->willReturn([]);

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

    public function testProcessForNestedAssociationWhenTitleAlreadyLoaded()
    {
        $associationName = 'testAssociation';
        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->addField($associationName)->setDataType('nestedAssociation');
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

    public function testProcessForEmptyResultOfNestedAssociation()
    {
        $associationName = 'testAssociation';
        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->addField($associationName)->setDataType('nestedAssociation');
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

    public function testProcessForNestedAssociationWhenParentEntityWasNotFound()
    {
        $associationName = 'testAssociation';
        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->addField($associationName)->setDataType('nestedAssociation');
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
}
