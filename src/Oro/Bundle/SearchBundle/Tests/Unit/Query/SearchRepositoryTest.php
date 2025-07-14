<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Query;

use Oro\Bundle\SearchBundle\Provider\AbstractSearchMappingProvider;
use Oro\Bundle\SearchBundle\Query\Factory\QueryFactoryInterface;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;
use Oro\Bundle\SearchBundle\Query\SearchRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SearchRepositoryTest extends TestCase
{
    private QueryFactoryInterface&MockObject $queryFactory;
    private AbstractSearchMappingProvider&MockObject $mappingProvider;
    private SearchRepository $repository;

    #[\Override]
    protected function setUp(): void
    {
        $this->queryFactory = $this->createMock(QueryFactoryInterface::class);
        $this->mappingProvider = $this->getMockBuilder(AbstractSearchMappingProvider::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getEntityAlias'])
            ->getMockForAbstractClass();

        $this->repository = new SearchRepository($this->queryFactory, $this->mappingProvider);
    }

    public function testCreateQueryWithoutEntity(): void
    {
        $query = $this->createMock(SearchQueryInterface::class);

        $this->queryFactory->expects(self::once())
            ->method('create')
            ->with([])
            ->willReturn($query);
        $this->mappingProvider->expects(self::never())
            ->method(self::anything());

        self::assertEquals($query, $this->repository->createQuery());
    }

    public function testCreateQueryWithEntity(): void
    {
        $entityClass = 'TestClass';
        $entityAlias = 'test_class';

        $query = $this->createMock(SearchQueryInterface::class);
        $query->expects(self::once())
            ->method('setFrom')
            ->with($entityAlias);

        $this->queryFactory->expects(self::once())
            ->method('create')
            ->with([])
            ->willReturn($query);

        $this->mappingProvider->expects(self::once())
            ->method('getEntityAlias')
            ->with($entityClass)
            ->willReturn($entityAlias);

        $this->repository->setEntityName($entityClass);
        self::assertEquals($query, $this->repository->createQuery());
    }

    public function testSetEntityName(): void
    {
        self::assertEmpty($this->repository->getEntityName());
        $this->repository->setEntityName('TestClass');
        self::assertEquals('TestClass', $this->repository->getEntityName());
    }

    public function testGetEntityName(): void
    {
        self::assertNull($this->repository->getEntityName());
        $this->repository->setEntityName('TestClass');
        self::assertEquals('TestClass', $this->repository->getEntityName());
    }

    public function testGetQueryFactory(): void
    {
        self::assertEquals($this->queryFactory, $this->repository->getQueryFactory());
    }

    public function testGetMappingProvider(): void
    {
        self::assertEquals($this->mappingProvider, $this->repository->getMappingProvider());
    }
}
