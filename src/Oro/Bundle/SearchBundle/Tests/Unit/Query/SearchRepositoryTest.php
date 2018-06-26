<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Query;

use Oro\Bundle\SearchBundle\Provider\AbstractSearchMappingProvider;
use Oro\Bundle\SearchBundle\Query\Factory\QueryFactoryInterface;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;
use Oro\Bundle\SearchBundle\Query\SearchRepository;

class SearchRepositoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var SearchRepository */
    protected $repository;

    /** @var QueryFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $queryFactory;

    /** @var AbstractSearchMappingProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $mappingProvider;

    protected function setUp()
    {
        $this->queryFactory = $this->createMock(QueryFactoryInterface::class);
        $this->mappingProvider = $this->getMockBuilder(AbstractSearchMappingProvider::class)
            ->disableOriginalConstructor()
            ->setMethods(['getEntityAlias'])
            ->getMockForAbstractClass();
        $this->repository = new SearchRepository($this->queryFactory, $this->mappingProvider);
    }

    public function testCreateQueryWithoutEntity()
    {
        $query = $this->createMock(SearchQueryInterface::class);

        $this->queryFactory->expects($this->once())
            ->method('create')
            ->with([])
            ->willReturn($query);
        $this->mappingProvider->expects($this->never())
            ->method($this->anything());

        $this->assertEquals($query, $this->repository->createQuery());
    }

    public function testCreateQueryWithEntity()
    {
        $entityClass = 'TestClass';
        $entityAlias = 'test_class';

        $query = $this->createMock(SearchQueryInterface::class);
        $query->expects($this->once())
            ->method('setFrom')
            ->with($entityAlias);

        $this->queryFactory->expects($this->once())
            ->method('create')
            ->with([])
            ->willReturn($query);

        $this->mappingProvider->expects($this->once())
            ->method('getEntityAlias')
            ->with($entityClass)
            ->willReturn($entityAlias);

        $this->repository->setEntityName($entityClass);
        $this->assertEquals($query, $this->repository->createQuery());
    }

    public function testSetEntityName()
    {
        $entityClass = 'TestClass';

        $this->assertAttributeEmpty('entityName', $this->repository);
        $this->repository->setEntityName($entityClass);
        $this->assertAttributeEquals($entityClass, 'entityName', $this->repository);
    }

    public function testGetEntityName()
    {
        $entityClass = 'TestClass';

        $this->assertNull($this->repository->getEntityName());
        $this->repository->setEntityName($entityClass);
        $this->assertEquals($entityClass, $this->repository->getEntityName());
    }

    public function testGetQueryFactory()
    {
        $this->assertEquals($this->queryFactory, $this->repository->getQueryFactory());
    }

    public function testGetMappingProvider()
    {
        $this->assertEquals($this->mappingProvider, $this->repository->getMappingProvider());
    }
}
