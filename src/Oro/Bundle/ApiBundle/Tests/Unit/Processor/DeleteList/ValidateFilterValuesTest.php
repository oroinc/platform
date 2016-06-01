<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\DeleteList;

use Oro\Bundle\ApiBundle\Filter\ComparisonFilter;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\DeleteList\ValidateFilterValues;
use Oro\Bundle\ApiBundle\Request\RestFilterValueAccessor;

class ValidateFilterValuesTest extends DeleteListProcessorTestCase
{
    /** @var ValidateFilterValues */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processor = new ValidateFilterValues();
    }

    public function testProcessWhenQueryIsAlreadyBuilt()
    {
        $this->context->setQuery(new \stdClass());
        $context = clone $this->context;
        $this->processor->process($this->context);
        $this->assertEquals($context, $this->context);
    }

    public function testProcess()
    {
        $filters       = $this->context->getFilters();
        $integerFilter = new ComparisonFilter('integer');
        $stringFilter  = new ComparisonFilter('string');
        $filters->add('id', $integerFilter);
        $filters->add('label', $stringFilter);

        $request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();
        $request->expects($this->once())
            ->method('getQueryString')
            ->willReturn('id=1&label=test');
        $filterValues = new RestFilterValueAccessor($request);

        $this->context->setFilterValues($filterValues);
        $this->processor->process($this->context);
    }

    public function testProcessWhenNoFilters()
    {
        $filters       = $this->context->getFilters();
        $integerFilter = new ComparisonFilter('integer');
        $stringFilter  = new ComparisonFilter('string');
        $filters->add('id', $integerFilter);
        $filters->add('label', $stringFilter);

        $request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();
        $request->expects($this->once())
            ->method('getQueryString')
            ->willReturn('');
        $filterValues = new RestFilterValueAccessor($request);

        $this->context->setFilterValues($filterValues);
        $this->processor->process($this->context);

        $this->assertEquals(
            [
                Error::createValidationError('filter constraint', 'At least one filter must be provided.')
            ],
            $this->context->getErrors()
        );
    }
}
