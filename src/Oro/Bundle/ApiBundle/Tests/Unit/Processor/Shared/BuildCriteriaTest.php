<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Collection\Criteria;
use Oro\Bundle\ApiBundle\Filter\ComparisonFilter;
use Oro\Bundle\ApiBundle\Filter\FilterValue;
use Oro\Bundle\ApiBundle\Filter\PageSizeFilter;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\Shared\BuildCriteria;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Tests\Unit\Filter\TestFilterValueAccessor;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorOrmRelatedTestCase;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;

class BuildCriteriaTest extends GetListProcessorOrmRelatedTestCase
{
    /** @var BuildCriteria */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processor = new BuildCriteria();
    }

    /**
     * @return Criteria
     */
    private function getCriteria()
    {
        $resolver = $this->createMock(EntityClassResolver::class);

        return new Criteria($resolver);
    }

    /**
     * @param string $dataType
     * @param string $propertyPath
     *
     * @return ComparisonFilter
     */
    private function getComparisonFilter($dataType, $propertyPath)
    {
        $filter = new ComparisonFilter($dataType);
        $filter->setSupportedOperators([ComparisonFilter::EQ, ComparisonFilter::NEQ]);
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
        $filterValues = new TestFilterValueAccessor();
        $filterValues->set(
            'filter[label]',
            FilterValue::createFromSource('filter[label]', 'label', 'val1', ComparisonFilter::EQ)
        );
        $filterValues->set(
            'filter[name]',
            FilterValue::createFromSource('filter[name]', 'name', 'val2', ComparisonFilter::EQ)
        );

        $filers = $this->context->getFilters();
        $filers->add('filter[label]', $this->getComparisonFilter('string', 'label'));
        $filers->add('filter[name]', $this->getComparisonFilter('string', 'association.name'));

        $this->context->setFilterValues($filterValues);
        $this->context->setCriteria($this->getCriteria());
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
        $filterValues = new TestFilterValueAccessor();
        $filterValues->set(
            'filter[label]',
            FilterValue::createFromSource('filter[label]', 'label', 'val1', ComparisonFilter::EQ)
        );
        $filterValues->set(
            'filter[name]',
            FilterValue::createFromSource('filter[name]', 'name', 'val2', ComparisonFilter::EQ)
        );

        $filers = $this->context->getFilters();
        $filers->add('filter[name]', $this->getComparisonFilter('string', 'association.name'));
        $filers->add('filter[label]', $this->getComparisonFilter('string', 'label'));

        $this->context->setFilterValues($filterValues);
        $this->context->setCriteria($this->getCriteria());
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
        $filterValues = new TestFilterValueAccessor();
        $filterValues->set(
            'filter[name]',
            FilterValue::createFromSource('filter[name]', 'name', 'val', ComparisonFilter::EQ)
        );

        $this->context->setFilterValues($filterValues);
        $this->context->setCriteria($this->getCriteria());
        $this->processor->process($this->context);

        self::assertNull(
            $this->context->getCriteria()->getWhereExpression()
        );
    }

    public function testProcessWhenApplyFilterFailed()
    {
        $filterValues = new TestFilterValueAccessor();
        $filterValues->set(
            'filter[name]',
            FilterValue::createFromSource('filter[name]', 'name', 'val', ComparisonFilter::EQ)
        );

        $filter = $this->createMock(ComparisonFilter::class);
        $exception = new \Exception('some error');

        $filers = $this->context->getFilters();
        $filers->add('filter[name]', $filter);

        $filter->expects(self::once())
            ->method('apply')
            ->willThrowException($exception);

        $this->context->setFilterValues($filterValues);
        $this->context->setCriteria($this->getCriteria());
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
        $filterValues = new TestFilterValueAccessor();
        $filterValues->set(
            'someFilter',
            new FilterValue('someFilter', 'val', ComparisonFilter::EQ)
        );

        $filter = $this->createMock(ComparisonFilter::class);
        $exception = new \Exception('some error');

        $filers = $this->context->getFilters();
        $filers->add('someFilter', $filter);

        $filter->expects(self::once())
            ->method('apply')
            ->willThrowException($exception);

        $this->context->setFilterValues($filterValues);
        $this->context->setCriteria($this->getCriteria());
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
        $filter = new PageSizeFilter('integer');
        $filter->setDefaultValue(5);

        $filers = $this->context->getFilters();
        $filers->add('pageSize', $filter);

        $this->context->setFilterValues(new TestFilterValueAccessor());
        $this->context->setCriteria($this->getCriteria());
        $this->processor->process($this->context);

        self::assertEquals(
            5,
            $this->context->getCriteria()->getMaxResults()
        );
    }
}
