<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Config\Config;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Processor\Shared\DataLoaderInterface;
use Oro\Bundle\ApiBundle\Processor\Shared\LoadEntityByDataLoader;
use Oro\Bundle\ApiBundle\Request\ApiActionGroup;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get\GetProcessorOrmRelatedTestCase;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Component\ChainProcessor\ParameterBag;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class LoadEntityByDataLoaderTest extends GetProcessorOrmRelatedTestCase
{
    /** @var DataLoaderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $dataLoader;

    /** @var EntityClassResolver|\PHPUnit\Framework\MockObject\MockObject */
    private $entityClassResolver;

    /** @var LoadEntityByDataLoader */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dataLoader = $this->createMock(DataLoaderInterface::class);
        $this->entityClassResolver = $this->createMock(EntityClassResolver::class);

        $this->processor = new LoadEntityByDataLoader(
            $this->dataLoader,
            $this->doctrineHelper,
            $this->entityClassResolver
        );
    }

    public function testProcessWhenEntityAlreadyLoaded(): void
    {
        $resultEntity = new Product();

        $this->context->setResult($resultEntity);
        $this->processor->process($this->context);

        self::assertSame($resultEntity, $this->context->getResult());
    }

    public function testProcessWithUnsupportedQuery(): void
    {
        $this->context->setQuery(new \stdClass());
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasResult());
    }

    public function testProcessWithoutConfig(): void
    {
        $entityClass = Group::class;

        $query = $this->doctrineHelper->createQueryBuilder($entityClass, 'e');

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->willReturn(new Config());

        $this->context->setClassName($entityClass);
        $this->context->setQuery($query);
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasResult());
    }

    public function testProcess(): void
    {
        $entityClass = Group::class;
        $entityData = ['id' => 123];
        $serializedEntityData = ['id' => 123, 'key' => 'val'];

        $query = $this->doctrineHelper->createQueryBuilder($entityClass, 'e');

        $entityDefinitionConfig = new EntityDefinitionConfig();
        $config = new Config();
        $config->setDefinition($entityDefinitionConfig);
        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->willReturn($config);

        $normalizationContext = [
            'action'      => $this->context->getAction(),
            'version'     => $this->context->getVersion(),
            'requestType' => $this->context->getRequestType(),
            'sharedData'  => $this->context->getSharedData()
        ];
        $this->dataLoader->expects(self::once())
            ->method('loadData')
            ->with(
                self::identicalTo($query),
                self::identicalTo($entityDefinitionConfig),
                $normalizationContext
            )
            ->willReturn([$entityData]);
        $this->dataLoader->expects(self::once())
            ->method('serializeData')
            ->with(
                [$entityData],
                self::identicalTo($entityDefinitionConfig),
                $normalizationContext
            )
            ->willReturn([$serializedEntityData]);

        $this->context->setClassName($entityClass);
        $this->context->setQuery($query);
        $this->processor->process($this->context);

        self::assertEquals($serializedEntityData, $this->context->getResult());
        self::assertEquals([ApiActionGroup::NORMALIZE_DATA], $this->context->getSkippedGroups());
    }

    public function testProcessWhenReturnedSeveralEntities(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The result must have one or zero items.');

        $entityClass = Group::class;

        $sharedData = new ParameterBag();
        $sharedData->set('someKey', 'someSharedValue');
        $this->context->setSharedData($sharedData);

        $query = $this->doctrineHelper->createQueryBuilder($entityClass, 'e');

        $entityDefinitionConfig = new EntityDefinitionConfig();
        $config = new Config();
        $config->setDefinition($entityDefinitionConfig);
        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->willReturn($config);

        $normalizationContext = [
            'action'      => $this->context->getAction(),
            'version'     => $this->context->getVersion(),
            'requestType' => $this->context->getRequestType(),
            'sharedData'  => $this->context->getSharedData()
        ];
        $this->dataLoader->expects(self::once())
            ->method('loadData')
            ->with(
                self::identicalTo($query),
                self::identicalTo($entityDefinitionConfig),
                $normalizationContext
            )
            ->willReturn([['id' => 123], ['id' => 234]]);
        $this->dataLoader->expects(self::never())
            ->method('serializeData');

        $this->context->setClassName($entityClass);
        $this->context->setQuery($query);
        $this->processor->process($this->context);
    }

    public function testProcessWhenEntityNotFound(): void
    {
        $entityClass = Group::class;
        $entityAlias = 'test';

        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(AbstractQuery::class);
        $qb->expects(self::once())
            ->method('getRootEntities')
            ->willReturn([$entityClass]);
        $qb->expects(self::once())
            ->method('getRootAliases')
            ->willReturn([$entityAlias]);
        $qb->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);
        $query->expects(self::once())
            ->method('getOneOrNullResult')
            ->willReturn(null);

        $entityDefinitionConfig = new EntityDefinitionConfig();
        $config = new Config();
        $config->setDefinition($entityDefinitionConfig);
        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->willReturn($config);

        $normalizationContext = [
            'action'      => $this->context->getAction(),
            'version'     => $this->context->getVersion(),
            'requestType' => $this->context->getRequestType(),
            'sharedData'  => $this->context->getSharedData()
        ];
        $this->dataLoader->expects(self::once())
            ->method('loadData')
            ->with(
                self::identicalTo($qb),
                self::identicalTo($entityDefinitionConfig),
                $normalizationContext
            )
            ->willReturn([]);
        $this->dataLoader->expects(self::never())
            ->method('serializeData');

        $this->entityClassResolver->expects(self::once())
            ->method('getEntityClass')
            ->with($entityClass)
            ->willReturn($entityClass);

        $this->context->setClassName($entityClass);
        $this->context->setQuery($qb);
        $this->processor->process($this->context);

        self::assertNull($this->context->getResult());
        self::assertEquals([ApiActionGroup::NORMALIZE_DATA], $this->context->getSkippedGroups());
    }

    public function testProcessWhenNoAccessToEntity(): void
    {
        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('No access to the entity.');

        $entityClass = Group::class;
        $entityAlias = 'test';

        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(AbstractQuery::class);
        $qb->expects(self::once())
            ->method('getRootEntities')
            ->willReturn([$entityClass]);
        $qb->expects(self::once())
            ->method('getRootAliases')
            ->willReturn([$entityAlias]);
        $qb->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);
        $query->expects(self::once())
            ->method('getOneOrNullResult')
            ->willReturn(['id' => 123]);

        $entityDefinitionConfig = new EntityDefinitionConfig();
        $config = new Config();
        $config->setDefinition($entityDefinitionConfig);
        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->willReturn($config);

        $normalizationContext = [
            'action'      => $this->context->getAction(),
            'version'     => $this->context->getVersion(),
            'requestType' => $this->context->getRequestType(),
            'sharedData'  => $this->context->getSharedData()
        ];
        $this->dataLoader->expects(self::once())
            ->method('loadData')
            ->with(
                self::identicalTo($qb),
                self::identicalTo($entityDefinitionConfig),
                $normalizationContext
            )
            ->willReturn([]);
        $this->dataLoader->expects(self::never())
            ->method('serializeData');

        $this->entityClassResolver->expects(self::once())
            ->method('getEntityClass')
            ->with($entityClass)
            ->willReturn($entityClass);

        $this->context->setClassName($entityClass);
        $this->context->setQuery($qb);
        $this->processor->process($this->context);
    }
}
