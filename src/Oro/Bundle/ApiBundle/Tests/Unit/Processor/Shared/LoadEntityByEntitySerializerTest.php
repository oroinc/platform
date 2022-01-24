<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Config\Config;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Processor\Shared\LoadEntityByEntitySerializer;
use Oro\Bundle\ApiBundle\Request\ApiActionGroup;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get\GetProcessorOrmRelatedTestCase;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Component\ChainProcessor\ParameterBag;
use Oro\Component\EntitySerializer\EntitySerializer;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class LoadEntityByEntitySerializerTest extends GetProcessorOrmRelatedTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|EntitySerializer */
    private $serializer;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityClassResolver */
    private $entityClassResolver;

    /** @var LoadEntityByEntitySerializer */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->serializer = $this->createMock(EntitySerializer::class);
        $this->entityClassResolver = $this->createMock(EntityClassResolver::class);

        $this->processor = new LoadEntityByEntitySerializer(
            $this->serializer,
            $this->doctrineHelper,
            $this->entityClassResolver
        );
    }

    public function testProcessWhenEntityAlreadyLoaded()
    {
        $resultEntity = new Product();

        $this->context->setResult($resultEntity);
        $this->processor->process($this->context);

        self::assertSame($resultEntity, $this->context->getResult());
    }

    public function testProcessWithUnsupportedQuery()
    {
        $this->context->setQuery(new \stdClass());
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasResult());
    }

    public function testProcessWithoutConfig()
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

    public function testProcess()
    {
        $entityClass = Group::class;
        $entityData = ['id' => 123];

        $query = $this->doctrineHelper->createQueryBuilder($entityClass, 'e');

        $entityDefinitionConfig = new EntityDefinitionConfig();
        $config = new Config();
        $config->setDefinition($entityDefinitionConfig);
        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->willReturn($config);

        $this->serializer->expects(self::once())
            ->method('serialize')
            ->with(
                self::identicalTo($query),
                self::identicalTo($entityDefinitionConfig),
                [
                    'action'      => $this->context->getAction(),
                    'version'     => $this->context->getVersion(),
                    'requestType' => $this->context->getRequestType(),
                    'sharedData'  => $this->context->getSharedData()
                ]
            )
            ->willReturn([$entityData]);

        $this->context->setClassName($entityClass);
        $this->context->setQuery($query);
        $this->processor->process($this->context);

        self::assertEquals($entityData, $this->context->getResult());
        self::assertEquals([ApiActionGroup::NORMALIZE_DATA], $this->context->getSkippedGroups());
    }

    public function testProcessWhenReturnedSeveralEntities()
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

        $this->serializer->expects(self::once())
            ->method('serialize')
            ->with(
                self::identicalTo($query),
                self::identicalTo($entityDefinitionConfig),
                [
                    'action'      => $this->context->getAction(),
                    'version'     => $this->context->getVersion(),
                    'requestType' => $this->context->getRequestType(),
                    'sharedData'  => $sharedData
                ]
            )
            ->willReturn([['id' => 123], ['id' => 234]]);

        $this->context->setClassName($entityClass);
        $this->context->setQuery($query);
        $this->processor->process($this->context);
    }

    public function testProcessWhenEntityNotFound()
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

        $this->serializer->expects(self::once())
            ->method('serialize')
            ->with(
                self::identicalTo($qb),
                self::identicalTo($entityDefinitionConfig),
                [
                    'action'      => $this->context->getAction(),
                    'version'     => $this->context->getVersion(),
                    'requestType' => $this->context->getRequestType(),
                    'sharedData'  => $this->context->getSharedData()
                ]
            )
            ->willReturn([]);

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

    public function testProcessWhenNoAccessToEntity()
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

        $this->serializer->expects(self::once())
            ->method('serialize')
            ->with(
                self::identicalTo($qb),
                self::identicalTo($entityDefinitionConfig),
                [
                    'action'      => $this->context->getAction(),
                    'version'     => $this->context->getVersion(),
                    'requestType' => $this->context->getRequestType(),
                    'sharedData'  => $this->context->getSharedData()
                ]
            )
            ->willReturn([]);

        $this->entityClassResolver->expects(self::once())
            ->method('getEntityClass')
            ->with($entityClass)
            ->willReturn($entityClass);

        $this->context->setClassName($entityClass);
        $this->context->setQuery($qb);
        $this->processor->process($this->context);
    }
}
