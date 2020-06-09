<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Query;

use Oro\Bundle\SearchBundle\Provider\AbstractSearchMappingProvider;
use Oro\Bundle\SearchBundle\Query\Factory\QueryFactoryInterface;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;
use Oro\Bundle\SearchBundle\Query\SearchRepository;
use PHPUnit\Framework\MockObject\MockObject;

class SearchRepositoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var SearchRepository */
    protected $repository;

    /** @var QueryFactoryInterface|MockObject */
    protected $queryFactory;

    /** @var AbstractSearchMappingProvider|MockObject */
    protected $mappingProvider;

    protected function setUp(): void
    {
        $this->queryFactory = $this->createMock(QueryFactoryInterface::class);
        $this->mappingProvider = $this->getMockBuilder(AbstractSearchMappingProvider::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getEntityAlias'])
            ->getMockForAbstractClass();
        $this->repository = new SearchRepository($this->queryFactory, $this->mappingProvider);
    }

    public function testCreateQueryWithoutEntity()
    {
        $query = $this->createMock(SearchQueryInterface::class);

        $this->queryFactory->expects(static::once())
            ->method('create')
            ->with([])
            ->willReturn($query);
        $this->mappingProvider->expects(static::never())->method(static::anything());

        static::assertEquals($query, $this->repository->createQuery());
    }

    public function testCreateQueryWithEntity()
    {
        $entityClass = 'TestClass';
        $entityAlias = 'test_class';

        $query = $this->createMock(SearchQueryInterface::class);
        $query->expects(static::once())
            ->method('setFrom')
            ->with($entityAlias);

        $this->queryFactory->expects(static::once())
            ->method('create')
            ->with([])
            ->willReturn($query);

        $this->mappingProvider->expects(static::once())
            ->method('getEntityAlias')
            ->with($entityClass)
            ->willReturn($entityAlias);

        $this->repository->setEntityName($entityClass);
        static::assertEquals($query, $this->repository->createQuery());
    }

    public function testSetEntityName()
    {
        static::assertEmpty($this->repository->getEntityName());
        $this->repository->setEntityName('TestClass');
        static::assertEquals('TestClass', $this->repository->getEntityName());
    }

    public function testGetEntityName()
    {
        static::assertNull($this->repository->getEntityName());
        $this->repository->setEntityName('TestClass');
        static::assertEquals('TestClass', $this->repository->getEntityName());
    }

    public function testGetQueryFactory()
    {
        static::assertEquals($this->queryFactory, $this->repository->getQueryFactory());
    }

    public function testGetMappingProvider()
    {
        static::assertEquals($this->mappingProvider, $this->repository->getMappingProvider());
    }
}
