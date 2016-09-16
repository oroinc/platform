<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;

use Oro\Bundle\ApiBundle\Collection\Criteria;
use Oro\Bundle\ApiBundle\Filter\ComparisonFilter;
use Oro\Bundle\ApiBundle\Processor\Shared\BuildCriteria;
use Oro\Bundle\ApiBundle\Request\RestFilterValueAccessor;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorOrmRelatedTestCase;

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
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getRequest($queryString)
    {
        $request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();
        $request->expects($this->any())
            ->method('getQueryString')
            ->willReturn($queryString);

        return $request;
    }

    /**
     * @return Criteria
     */
    protected function getCriteria()
    {
        $resolver = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\EntityClassResolver')
            ->disableOriginalConstructor()
            ->getMock();

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
        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $this->context->setQuery($qb);
        $this->processor->process($this->context);

        $this->assertSame($qb, $this->context->getQuery());
    }

    public function testProcessWhenCriteriaObjectDoesNotExist()
    {
        $this->processor->process($this->context);

        $this->assertFalse($this->context->hasQuery());
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

        $this->assertEquals(
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

    public function testProcessForUnknownFilter()
    {
        $request = $this->getRequest('filter[name]=val1');

        $this->context->setFilterValues(new RestFilterValueAccessor($request));
        $this->context->setCriteria($this->getCriteria());
        $this->processor->process($this->context);

        $this->assertNull(
            $this->context->getCriteria()->getWhereExpression()
        );
    }
}
