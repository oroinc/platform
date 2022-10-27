<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\Shared;

use Oro\Bundle\ApiBundle\Config\Config;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\FieldMetadata;
use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\LoadExtendedAssociation;
use Oro\Bundle\ApiBundle\Request\ApiActionGroup;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\GetSubresourceProcessorOrmRelatedTestCase;
use Oro\Bundle\ApiBundle\Util\EntityIdHelper;
use Oro\Component\EntitySerializer\EntitySerializer;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class LoadExtendedAssociationTest extends GetSubresourceProcessorOrmRelatedTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|EntitySerializer */
    private $entitySerializer;

    /** @var LoadExtendedAssociation */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entitySerializer = $this->createMock(EntitySerializer::class);

        $this->processor = new LoadExtendedAssociation(
            $this->entitySerializer,
            $this->doctrineHelper,
            new EntityIdHelper(),
            $this->configProvider
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

    public function testProcessForInvalidTypeOfExtendedAssociation()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported type of extended association: invalidType.');

        $associationName = 'testAssociation';
        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->addField($associationName)->setDataType('association:invalidType');

        $this->context->setAssociationName($associationName);
        $this->context->setParentConfig($parentConfig);
        $this->processor->process($this->context);
    }

    public function testProcessForExtendedAssociation()
    {
        $associationName = 'testAssociation';
        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->addField($associationName)->setDataType('association:manyToOne:kind');
        $parentMetadata = new EntityMetadata('Test\Entity');
        $parentMetadata->setIdentifierFieldNames(['id']);
        $parentMetadata->addField(new FieldMetadata('id'));
        $parentClassName = Entity\Product::class;
        $parentId = 123;

        $loadedData = [
            ['id' => $parentId, $associationName => ['id' => 1]]
        ];

        $parentConfigContainer = new Config();
        $parentConfigContainer->setDefinition($parentConfig);
        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->willReturn($parentConfigContainer);

        $expectedQueryBuilder = $this->doctrineHelper
            ->createQueryBuilder($parentClassName, 'e')
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

        self::assertEquals(
            ['id' => 1],
            $this->context->getResult()
        );
        self::assertEquals([ApiActionGroup::NORMALIZE_DATA], $this->context->getSkippedGroups());
    }

    public function testProcessForEmptyResultOfManyToOneExtendedAssociation()
    {
        $associationName = 'testAssociation';
        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->addField($associationName)->setDataType('association:manyToOne:kind');
        $parentMetadata = new EntityMetadata('Test\Entity');
        $parentMetadata->setIdentifierFieldNames(['id']);
        $parentMetadata->addField(new FieldMetadata('id'));
        $parentClassName = Entity\Product::class;
        $parentId = 123;

        $loadedData = [
            ['id' => $parentId, $associationName => null]
        ];

        $parentConfigContainer = new Config();
        $parentConfigContainer->setDefinition($parentConfig);
        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->willReturn($parentConfigContainer);

        $expectedQueryBuilder = $this->doctrineHelper
            ->createQueryBuilder($parentClassName, 'e')
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
        self::assertEquals([ApiActionGroup::NORMALIZE_DATA], $this->context->getSkippedGroups());
    }

    public function testProcessForEmptyResultOfManyToManyExtendedAssociation()
    {
        $associationName = 'testAssociation';
        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->addField($associationName)->setDataType('association:manyToMany:kind');
        $parentMetadata = new EntityMetadata('Test\Entity');
        $parentMetadata->setIdentifierFieldNames(['id']);
        $parentMetadata->addField(new FieldMetadata('id'));
        $parentClassName = Entity\Product::class;
        $parentId = 123;

        $loadedData = [
            ['id' => $parentId, $associationName => []]
        ];

        $parentConfigContainer = new Config();
        $parentConfigContainer->setDefinition($parentConfig);
        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->willReturn($parentConfigContainer);

        $expectedQueryBuilder = $this->doctrineHelper
            ->createQueryBuilder($parentClassName, 'e')
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
        self::assertEquals([ApiActionGroup::NORMALIZE_DATA], $this->context->getSkippedGroups());
    }

    public function testProcessForEmptyResultOfMultipleManyToOneExtendedAssociation()
    {
        $associationName = 'testAssociation';
        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->addField($associationName)->setDataType('association:multipleManyToOne:kind');
        $parentMetadata = new EntityMetadata('Test\Entity');
        $parentMetadata->setIdentifierFieldNames(['id']);
        $parentMetadata->addField(new FieldMetadata('id'));
        $parentClassName = Entity\Product::class;
        $parentId = 123;

        $loadedData = [
            ['id' => $parentId, $associationName => []]
        ];

        $parentConfigContainer = new Config();
        $parentConfigContainer->setDefinition($parentConfig);
        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->willReturn($parentConfigContainer);

        $expectedQueryBuilder = $this->doctrineHelper
            ->createQueryBuilder($parentClassName, 'e')
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
        self::assertEquals([ApiActionGroup::NORMALIZE_DATA], $this->context->getSkippedGroups());
    }

    public function testProcessForManyToOneExtendedAssociationWhenParentEntityWasNotFound()
    {
        $associationName = 'testAssociation';
        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->addField($associationName)->setDataType('association:manyToOne:kind');
        $parentMetadata = new EntityMetadata('Test\Entity');
        $parentMetadata->setIdentifierFieldNames(['id']);
        $parentMetadata->addField(new FieldMetadata('id'));
        $parentClassName = Entity\Product::class;
        $parentId = 123;

        $loadedData = [];

        $parentConfigContainer = new Config();
        $parentConfigContainer->setDefinition($parentConfig);
        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->willReturn($parentConfigContainer);

        $expectedQueryBuilder = $this->doctrineHelper
            ->createQueryBuilder($parentClassName, 'e')
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
        self::assertEquals([ApiActionGroup::NORMALIZE_DATA], $this->context->getSkippedGroups());
    }

    public function testProcessForManyToManyExtendedAssociationWhenParentEntityWasNotFound()
    {
        $associationName = 'testAssociation';
        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->addField($associationName)->setDataType('association:manyToMany:kind');
        $parentMetadata = new EntityMetadata('Test\Entity');
        $parentMetadata->setIdentifierFieldNames(['id']);
        $parentMetadata->addField(new FieldMetadata('id'));
        $parentClassName = Entity\Product::class;
        $parentId = 123;

        $loadedData = [];

        $parentConfigContainer = new Config();
        $parentConfigContainer->setDefinition($parentConfig);
        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->willReturn($parentConfigContainer);

        $expectedQueryBuilder = $this->doctrineHelper
            ->createQueryBuilder($parentClassName, 'e')
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
        self::assertEquals([ApiActionGroup::NORMALIZE_DATA], $this->context->getSkippedGroups());
    }

    public function testProcessForMultipleManyToOneExtendedAssociationWhenParentEntityWasNotFound()
    {
        $associationName = 'testAssociation';
        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->addField($associationName)->setDataType('association:multipleManyToOne:kind');
        $parentMetadata = new EntityMetadata('Test\Entity');
        $parentMetadata->setIdentifierFieldNames(['id']);
        $parentMetadata->addField(new FieldMetadata('id'));
        $parentClassName = Entity\Product::class;
        $parentId = 123;

        $loadedData = [];

        $parentConfigContainer = new Config();
        $parentConfigContainer->setDefinition($parentConfig);
        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->willReturn($parentConfigContainer);

        $expectedQueryBuilder = $this->doctrineHelper
            ->createQueryBuilder($parentClassName, 'e')
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
        self::assertEquals([ApiActionGroup::NORMALIZE_DATA], $this->context->getSkippedGroups());
    }
}
