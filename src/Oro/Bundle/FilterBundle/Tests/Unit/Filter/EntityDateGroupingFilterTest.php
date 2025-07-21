<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Filter;

use Doctrine\ORM\Query\Expr\Select;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Filter\EntityDateGroupingFilter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormFactoryInterface;

class EntityDateGroupingFilterTest extends TestCase
{
    private FormFactoryInterface&MockObject $formFactory;
    private ManagerRegistry&MockObject $doctrine;
    private EntityDateGroupingFilter $filter;

    #[\Override]
    protected function setUp(): void
    {
        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->filter = new EntityDateGroupingFilter($this->formFactory, new FilterUtility());
        $this->filter->init(EntityDateGroupingFilter::NAME, [
            'column_name' => 'timePeriod',
            'column_day' => 'e.searchDateDay',
            'column_month' => 'e.searchDateMonth',
            'column_quarter' => 'e.searchDateQuarter',
            'column_year' => 'e.searchDateYear'
        ]);
    }

    public function testApplyOrderBy(): void
    {
        $select = new Select(['CONCAT(e.searchDateYear, \'-\', e.searchDateMonth) as timePeriod']);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects(self::once())
            ->method('getDQLPart')
            ->with('select')
            ->willReturn([$select]);

        $queryBuilder->expects(self::exactly(2))
            ->method('addOrderBy')
            ->withConsecutive(
                ['e.searchDateYear', 'DESC'],
                ['e.searchDateMonth', 'DESC'],
            );

        $datasource = $this->createMock(OrmDatasource::class);
        $datasource->expects(self::once())
            ->method('getQueryBuilder')
            ->willReturn($queryBuilder);

        $this->filter->applyOrderBy($datasource, 'e.searchDate', 'desc');
    }

    /**
     * @dataProvider applyDataProvider
     */
    public function testApply(string $type, string $select, array $groupBy): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects(self::once())
            ->method('addSelect')
            ->with($select);
        $queryBuilder->expects(self::once())
            ->method('addGroupBy')
            ->with(...$groupBy);

        $datasource = $this->createMock(OrmFilterDatasourceAdapter::class);
        $datasource->expects(self::once())
            ->method('getQueryBuilder')
            ->willReturn($queryBuilder);

        $this->filter->apply($datasource, ['value' => $type]);
    }

    public function applyDataProvider()
    {
        return [
            'day filter' => [
                'type' => 'day',
                'select' => 'CONCAT(e.searchDateDay, \'-\', e.searchDateMonth, \'-\', e.searchDateYear) AS timePeriod',
                'groupBy' => ['e.searchDateYear', 'e.searchDateMonth', 'e.searchDateDay'],
            ],
            'month filter' => [
                'type' => 'month',
                'select' => 'CONCAT(e.searchDateMonth, \'-\', e.searchDateYear) AS timePeriod',
                'groupBy' => ['e.searchDateYear', 'e.searchDateMonth'],
            ],
            'quarter filter' => [
                'type' => 'quarter',
                'select' => 'CONCAT(e.searchDateQuarter, \'-\', e.searchDateYear) AS timePeriod',
                'groupBy' => ['e.searchDateYear', 'e.searchDateQuarter'],
            ],
            'year filter' => [
                'type' => 'year',
                'select' => 'e.searchDateYear AS timePeriod',
                'groupBy' => ['e.searchDateYear'],
            ]
        ];
    }
}
