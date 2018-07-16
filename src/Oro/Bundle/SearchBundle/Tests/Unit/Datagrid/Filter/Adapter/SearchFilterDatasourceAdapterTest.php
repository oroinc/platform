<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Datagrid\Filter\Adapter;

use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Form\Type\Filter\TextFilterType;
use Oro\Bundle\SearchBundle\Datagrid\Filter\Adapter\SearchFilterDatasourceAdapter;
use Oro\Bundle\SearchBundle\Datagrid\Filter\SearchStringFilter;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;
use Symfony\Component\Form\FormFactoryInterface;

class SearchFilterDatasourceAdapterTest extends \PHPUnit\Framework\TestCase
{
    /** @var SearchQueryInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $searchQuery;

    protected function setUp()
    {
        $this->searchQuery = $this->createMock(SearchQueryInterface::class);
    }

    public function testAddRestrictionAndCondition()
    {
        $restriction = Criteria::expr()->eq('foo', 'bar');

        $criteria = $this->createMock(Criteria::class);
        $criteria->expects($this->once())
            ->method('andWhere')
            ->with($restriction);

        $query = $this->createMock(Query::class);
        $query->expects($this->once())
            ->method('getCriteria')
            ->willReturn($criteria);

        $this->searchQuery->expects($this->any())
            ->method('getQuery')
            ->willReturn($query);

        $adapter = new SearchFilterDatasourceAdapter($this->searchQuery);
        $adapter->addRestriction($restriction, FilterUtility::CONDITION_AND);
    }

    public function testAddRestrictionOrCondition()
    {
        $restriction = Criteria::expr()->eq('foo', 'bar');

        $criteria = $this->createMock(Criteria::class);
        $criteria->expects($this->once())
            ->method('orWhere')
            ->with($restriction);

        $query = $this->createMock(Query::class);
        $query->expects($this->once())
            ->method('getCriteria')
            ->willReturn($criteria);

        $this->searchQuery->expects($this->any())
            ->method('getQuery')
            ->willReturn($query);

        $adapter = new SearchFilterDatasourceAdapter($this->searchQuery);
        $adapter->addRestriction($restriction, FilterUtility::CONDITION_OR);
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Restriction not supported.
     */
    public function testAddRestrictionInvalidCondition()
    {
        $adapter = new SearchFilterDatasourceAdapter($this->searchQuery);
        $adapter->addRestriction(Criteria::expr()->eq('foo', 'bar'), 'invalid_condition');
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Restriction not supported.
     */
    public function testAddRestrictionInvalidRestriction()
    {
        $adapter = new SearchFilterDatasourceAdapter($this->searchQuery);
        $adapter->addRestriction('foo = bar', FilterUtility::CONDITION_OR);
    }

    public function testInApplyFromStringFilter()
    {
        $criteria = $this->createMock(Criteria::class);
        $criteria->expects($this->once())
            ->method('andWhere')
            ->with(Criteria::expr()->contains('foo', 'bar'));

        $query = $this->createMock(Query::class);
        $query->expects($this->once())
            ->method('getCriteria')
            ->willReturn($criteria);

        $this->searchQuery->expects($this->any())
            ->method('getQuery')
            ->willReturn($query);

        $formFactory = $this->createMock(FormFactoryInterface::class);

        $stringFilter = new SearchStringFilter($formFactory, new FilterUtility());
        $stringFilter->init('test', [
            FilterUtility::DATA_NAME_KEY => 'foo',
            FilterUtility::MIN_LENGTH_KEY => 0,
            FilterUtility::MAX_LENGTH_KEY => 100,
            FilterUtility::FORCE_LIKE_KEY => false,
        ]);

        $ds = new SearchFilterDatasourceAdapter($this->searchQuery);
        $stringFilter->apply($ds, ['type' => TextFilterType::TYPE_CONTAINS, 'value' => 'bar']);
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Method currently not supported.
     */
    public function testGroupBy()
    {
        $ds = new SearchFilterDatasourceAdapter($this->searchQuery);
        $ds->groupBy('name');
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Method currently not supported.
     */
    public function testAddGroupBy()
    {
        $ds = new SearchFilterDatasourceAdapter($this->searchQuery);
        $ds->addGroupBy('name');
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Method currently not supported.
     */
    public function testSetParameter()
    {
        $ds = new SearchFilterDatasourceAdapter($this->searchQuery);
        $ds->setParameter('key', 'value');
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Method currently not supported.
     */
    public function testGetFieldByAlias()
    {
        $ds = new SearchFilterDatasourceAdapter($this->searchQuery);
        $ds->getFieldByAlias('name');
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Query not initialized properly
     */
    public function testGetWrappedSearchQueryNotInitialized()
    {
        $ds = new SearchFilterDatasourceAdapter($this->searchQuery);
        $ds->getWrappedSearchQuery();
    }

    public function testGetWrappedSearchQuery()
    {
        $query = $this->createMock(Query::class);
        $this->searchQuery->expects($this->any())
            ->method('getQuery')
            ->willReturn($query);

        $ds = new SearchFilterDatasourceAdapter($this->searchQuery);
        $this->assertEquals($ds->getWrappedSearchQuery(), $query);
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Use Criteria::expr() instead.
     */
    public function testExpr()
    {
        $ds = new SearchFilterDatasourceAdapter($this->searchQuery);
        $ds->expr();
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Method currently not supported.
     */
    public function testGetDatabasePlatform()
    {
        $ds = new SearchFilterDatasourceAdapter($this->searchQuery);
        $ds->getDatabasePlatform();
    }

    public function testGenerateParameterName()
    {
        $name = 'testName';

        $ds = new SearchFilterDatasourceAdapter($this->searchQuery);
        $this->assertEquals($name, $ds->generateParameterName($name));
    }

    public function testGetQuery()
    {
        $ds = new SearchFilterDatasourceAdapter($this->searchQuery);
        $this->assertEquals($this->searchQuery, $ds->getSearchQuery());
    }
}
