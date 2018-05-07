<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Filter\ComparisonFilter;
use Oro\Bundle\ApiBundle\Filter\FilterValue;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\Shared\NormalizeFilterValues;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Tests\Unit\Filter\TestFilterValueAccessor;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorTestCase;

class NormalizeFilterValuesTest extends GetListProcessorTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|ValueNormalizer */
    private $valueNormalizer;

    /** @var NormalizeFilterValues */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->valueNormalizer = $this->createMock(ValueNormalizer::class);

        $this->processor = new NormalizeFilterValues($this->valueNormalizer);
    }

    public function testProcessOnExistingQuery()
    {
        $this->context->setQuery(new \stdClass());
        $context = clone $this->context;
        $this->processor->process($this->context);
        self::assertEquals($context, $this->context);
    }

    public function testProcess()
    {
        $filters = $this->context->getFilters();
        $integerFilter = new ComparisonFilter('integer');
        $stringFilter = new ComparisonFilter('string');
        $filters->add('id', $integerFilter);
        $filters->add('label', $stringFilter);

        $filterValues = new TestFilterValueAccessor();
        $filterValues->set('id', new FilterValue('id', '1'));
        $filterValues->set('label', new FilterValue('label', 'test'));

        $this->valueNormalizer->expects(self::exactly(2))
            ->method('normalizeValue')
            ->willReturnMap(
                [
                    ['1', 'integer', $this->context->getRequestType(), false, false, 1],
                    ['test', 'string', $this->context->getRequestType(), false, false, 'test']
                ]
            );

        $this->context->setFilterValues($filterValues);
        $this->processor->process($this->context);

        self::assertTrue(is_int($filterValues->get('id')->getValue()));
        self::assertEquals(1, $filterValues->get('id')->getValue());
        self::assertTrue(is_string($filterValues->get('label')->getValue()));
        self::assertEquals('test', $filterValues->get('label')->getValue());

        self::assertFalse($this->context->hasErrors());
    }

    public function testProcessForInvalidDataType()
    {
        $filters = $this->context->getFilters();
        $integerFilter = new ComparisonFilter('integer');
        $filters->add('id', $integerFilter);

        $exception = new \UnexpectedValueException('invalid data type');

        $filterValues = new TestFilterValueAccessor();
        $filterValues->set('id', new FilterValue('id', 'invalid'));

        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with('invalid', 'integer', $this->context->getRequestType(), false, false)
            ->willThrowException($exception);

        $this->context->setFilterValues($filterValues);
        $this->processor->process($this->context);

        self::assertEquals('invalid', $filterValues->get('id')->getValue());

        self::assertEquals(
            [
                Error::createValidationError(Constraint::FILTER)
                    ->setInnerException($exception)
                    ->setSource(ErrorSource::createByParameter('id'))
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessForNotSupportedFilter()
    {
        $filters = $this->context->getFilters();
        $integerFilter = new ComparisonFilter('string');
        $filters->add('label', $integerFilter);

        $filterValues = new TestFilterValueAccessor();
        $filterValues->set('id', new FilterValue('id', '1'));
        $filterValues->set('label', new FilterValue('label', 'test'));

        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with('test', 'string', $this->context->getRequestType(), false, false)
            ->willReturn('test');

        $this->context->setFilterValues($filterValues);
        $this->processor->process($this->context);

        self::assertEquals(
            [
                Error::createValidationError(Constraint::FILTER, 'The filter is not supported.')
                    ->setSource(ErrorSource::createByParameter('id'))
            ],
            $this->context->getErrors()
        );
    }
}
