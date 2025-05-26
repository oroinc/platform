<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Doctrine\ORM\Query;
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
use Oro\Component\DoctrineUtils\ORM\QueryHintResolverInterface;
use Oro\Component\EntitySerializer\EntitySerializer;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class LoadEntityByEntitySerializerTest extends GetProcessorOrmRelatedTestCase
{
    private EntitySerializer&MockObject $serializer;
    private EntityClassResolver&MockObject $entityClassResolver;
    private QueryHintResolverInterface&MockObject $queryHintResolver;
    private LoadEntityByEntitySerializer $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->serializer = $this->createMock(EntitySerializer::class);
        $this->entityClassResolver = $this->createMock(EntityClassResolver::class);
        $this->queryHintResolver = $this->createMock(QueryHintResolverInterface::class);

        $this->processor = new LoadEntityByEntitySerializer(
            $this->serializer,
            $this->doctrineHelper,
            $this->entityClassResolver,
            $this->queryHintResolver
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

    public function testProcessWhenEntityNotFound(): void
    {
        $entityClass = Group::class;
        $entityAlias = 'test';

        $query = new Query($this->em);
        $query->setDQL(sprintf('SELECT %2$s.id FROM %1$s AS %2$s', $entityClass, $entityAlias));
        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects(self::once())
            ->method('getRootEntities')
            ->willReturn([$entityClass]);
        $qb->expects(self::once())
            ->method('getRootAliases')
            ->willReturn([$entityAlias]);
        $qb->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);
        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            $query->getSQL(),
            []
        );

        $entityDefinitionConfig = new EntityDefinitionConfig();
        $entityDefinitionConfig->addHint('HINT_TEST');
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

        $this->queryHintResolver->expects(self::once())
            ->method('resolveHints')
            ->with(self::identicalTo($query), ['HINT_TEST']);

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

        $query = new Query($this->em);
        $query->setDQL(sprintf('SELECT %2$s.id FROM %1$s AS %2$s', $entityClass, $entityAlias));
        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects(self::once())
            ->method('getRootEntities')
            ->willReturn([$entityClass]);
        $qb->expects(self::once())
            ->method('getRootAliases')
            ->willReturn([$entityAlias]);
        $qb->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);
        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            $query->getSQL(),
            [['id_0' => 123]]
        );

        $entityDefinitionConfig = new EntityDefinitionConfig();
        $entityDefinitionConfig->addHint('HINT_TEST');
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

        $this->queryHintResolver->expects(self::once())
            ->method('resolveHints')
            ->with(self::identicalTo($query), ['HINT_TEST']);

        $this->context->setClassName($entityClass);
        $this->context->setQuery($qb);
        $this->processor->process($this->context);
    }
}
