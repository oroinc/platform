<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Filter\ComparisonFilter;
use Oro\Bundle\ApiBundle\Filter\FilterOperator;
use Oro\Bundle\ApiBundle\Filter\FilterValue;
use Oro\Bundle\ApiBundle\Filter\PageSizeFilter;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\Shared\BuildCriteria;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorOrmRelatedTestCase;

class BuildCriteriaTest extends GetListProcessorOrmRelatedTestCase
{
    /** @var BuildCriteria */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = new BuildCriteria();
    }

    private function getComparisonFilter(string $dataType, string $propertyPath): ComparisonFilter
    {
        $filter = new ComparisonFilter($dataType);
        $filter->setSupportedOperators([FilterOperator::EQ, FilterOperator::NEQ]);
        $filter->setField($propertyPath);

        return $filter;
    }

    public function testProcessWhenQueryIsAlreadyBuilt()
    {
        $qb = $this->createMock(QueryBuilder::class);

        $this->context->setQuery($qb);
        $this->processor->process($this->context);

        self::assertSame($qb, $this->context->getQuery());
    }

    public function testProcessWhenCriteriaObjectDoesNotExist()
    {
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasQuery());
    }

    public function testProcess()
    {
        $filterValues = $this->context->getFilterValues();
        $filterValues->set(
            'filter[label]',
            FilterValue::createFromSource('filter[label]', 'label', 'val1', FilterOperator::EQ)
        );
        $filterValues->set(
            'filter[name]',
            FilterValue::createFromSource('filter[name]', 'name', 'val2', FilterOperator::EQ)
        );

        $filers = $this->context->getFilters();
        $filers->add('filter[label]', $this->getComparisonFilter('string', 'label'));
        $filers->add('filter[name]', $this->getComparisonFilter('string', 'association.name'));

        $this->context->setFilterValues($filterValues);
        $this->context->setCriteria(new Criteria());
        $this->processor->process($this->context);

        self::assertEquals(
            new CompositeExpression(
                'AND',
                [
                    new Comparison('label', '=', 'val1'),
                    new Comparison('association.name', '=', 'val2')
                ]
            ),
            $this->context->getCriteria()->getWhereExpression()
        );
    }

    public function testProcessShouldApplyFiltersInCorrectOrder()
    {
        $filterValues = $this->context->getFilterValues();
        $filterValues->set(
            'filter[label]',
            FilterValue::createFromSource('filter[label]', 'label', 'val1', FilterOperator::EQ)
        );
        $filterValues->set(
            'filter[name]',
            FilterValue::createFromSource('filter[name]', 'name', 'val2', FilterOperator::EQ)
        );

        $filers = $this->context->getFilters();
        $filers->add('filter[name]', $this->getComparisonFilter('string', 'association.name'));
        $filers->add('filter[label]', $this->getComparisonFilter('string', 'label'));

        $this->context->setFilterValues($filterValues);
        $this->context->setCriteria(new Criteria());
        $this->processor->process($this->context);

        self::assertEquals(
            new CompositeExpression(
                'AND',
                [
                    new Comparison('association.name', '=', 'val2'),
                    new Comparison('label', '=', 'val1')
                ]
            ),
            $this->context->getCriteria()->getWhereExpression()
        );
    }

    public function testProcessForUnknownFilter()
    {
        $filterValues = $this->context->getFilterValues();
        $filterValues->set(
            'filter[name]',
            FilterValue::createFromSource('filter[name]', 'name', 'val', FilterOperator::EQ)
        );

        $this->context->setFilterValues($filterValues);
        $this->context->setCriteria(new Criteria());
        $this->processor->process($this->context);

        self::assertNull(
            $this->context->getCriteria()->getWhereExpression()
        );
    }

    public function testProcessWhenApplyFilterFailed()
    {
        $filterValues = $this->context->getFilterValues();
        $filterValues->set(
            'filter[name]',
            FilterValue::createFromSource('filter[name]', 'name', 'val', FilterOperator::EQ)
        );

        $filter = $this->createMock(ComparisonFilter::class);
        $exception = new \Exception('some error');

        $filers = $this->context->getFilters();
        $filers->add('filter[name]', $filter);

        $filter->expects(self::once())
            ->method('apply')
            ->willThrowException($exception);

        $this->context->setFilterValues($filterValues);
        $this->context->setCriteria(new Criteria());
        $this->processor->process($this->context);

        self::assertNull($this->context->getCriteria()->getWhereExpression());
        self::assertEquals(
            [
                Error::createValidationError(Constraint::FILTER)
                    ->setInnerException($exception)
                    ->setSource(ErrorSource::createByParameter('filter[name]'))
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWhenApplyPredefinedFilterFailed()
    {
        $filterValues = $this->context->getFilterValues();
        $filterValues->set(
            'someFilter',
            new FilterValue('someFilter', 'val', FilterOperator::EQ)
        );

        $filter = $this->createMock(ComparisonFilter::class);
        $exception = new \Exception('some error');

        $filers = $this->context->getFilters();
        $filers->add('someFilter', $filter);

        $filter->expects(self::once())
            ->method('apply')
            ->willThrowException($exception);

        $this->context->setFilterValues($filterValues);
        $this->context->setCriteria(new Criteria());
        $this->processor->process($this->context);

        self::assertNull($this->context->getCriteria()->getWhereExpression());
        self::assertEquals(
            [
                Error::createByException($exception)
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessFilterWithDefaultValue()
    {
        $filter = new PageSizeFilter(DataType::INTEGER);
        $filter->setDefaultValue(5);

        $filers = $this->context->getFilters();
        $filers->add('pageSize', $filter);

        $this->context->setCriteria(new Criteria());
        $this->processor->process($this->context);

        self::assertEquals(
            5,
            $this->context->getCriteria()->getMaxResults()
        );
    }
}
