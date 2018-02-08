<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\ORM\QueryBuilder;

use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\ApiBundle\Collection\Criteria;
use Oro\Bundle\ApiBundle\Filter\ComparisonFilter;
use Oro\Bundle\ApiBundle\Filter\FilterValue;
use Oro\Bundle\ApiBundle\Filter\PageSizeFilter;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\Shared\BuildCriteria;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Request\RestFilterValueAccessor;
use Oro\Bundle\ApiBundle\Tests\Unit\Filter\TestFilterValueAccessor;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorOrmRelatedTestCase;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;

class BuildCriteriaTest extends GetListProcessorOrmRelatedTestCase
{
    /** @var BuildCriteria */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processor = new BuildCriteria();
    }

    /**
     * @param $queryString
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|Request
     */
    protected function getRequest($queryString)
    {
        $request = $this->createMock(Request::class);
        $request->expects(self::any())
            ->method('getQueryString')
            ->willReturn($queryString);

        return $request;
    }

    /**
     * @return Criteria
     */
    protected function getCriteria()
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
    protected function getComparisonFilter($dataType, $propertyPath)
    {
        $filter = new ComparisonFilter($dataType);
        $filter->setSupportedOperators(['=', '!=']);
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
        $request = $this->getRequest('filter[label]=val1&filter[name]=val2');

        $filers = $this->context->getFilters();
        $filers->add('filter[label]', $this->getComparisonFilter('string', 'label'));
        $filers->add('filter[name]', $this->getComparisonFilter('string', 'association.name'));

        $this->context->setFilterValues(new RestFilterValueAccessor($request));
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
        $request = $this->getRequest('filter[label]=val1&filter[name]=val2');

        $filers = $this->context->getFilters();
        $filers->add('filter[name]', $this->getComparisonFilter('string', 'association.name'));
        $filers->add('filter[label]', $this->getComparisonFilter('string', 'label'));

        $this->context->setFilterValues(new RestFilterValueAccessor($request));
        $this->context->setCriteria($this->getCriteria());
        $this->processor->process($this->context);

        self::assertEquals(
            new CompositeExpression(
                'AND',
                [
                    new Comparison('association.name', '=', 'val2'),
                    new Comparison('label', '=', 'val1'),
                ]
            ),
            $this->context->getCriteria()->getWhereExpression()
        );
    }

    public function testProcessForUnknownFilter()
    {
        $request = $this->getRequest('filter[name]=val1');

        $this->context->setFilterValues(new RestFilterValueAccessor($request));
        $this->context->setCriteria($this->getCriteria());
        $this->processor->process($this->context);

        self::assertNull(
            $this->context->getCriteria()->getWhereExpression()
        );
    }

    public function testProcessWhenApplyFilterFailed()
    {
        $request = $this->getRequest('filter[name]=val');

        $filter = $this->createMock(ComparisonFilter::class);
        $exception = new \Exception('some error');

        $filers = $this->context->getFilters();
        $filers->add('filter[name]', $filter);

        $filter->expects(self::once())
            ->method('apply')
            ->willThrowException($exception);

        $this->context->setFilterValues(new RestFilterValueAccessor($request));
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
        $filterValues->set('someFilter', new FilterValue('someFilter', 'val'));

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
        $request = $this->getRequest('');

        $filter = new PageSizeFilter('integer');
        $filter->setDefaultValue(5);

        $filers = $this->context->getFilters();
        $filers->add('pageSize', $filter);

        $this->context->setFilterValues(new RestFilterValueAccessor($request));
        $this->context->setCriteria($this->getCriteria());
        $this->processor->process($this->context);

        self::assertEquals(
            5,
            $this->context->getCriteria()->getMaxResults()
        );
    }
}
