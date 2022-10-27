<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Config\Extra\FilterFieldsConfigExtra;
use Oro\Bundle\ApiBundle\Filter\FilterNames;
use Oro\Bundle\ApiBundle\Filter\FilterNamesRegistry;
use Oro\Bundle\ApiBundle\Filter\FilterValue;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\Shared\HandleFieldsFilter;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get\GetProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Oro\Component\Testing\Unit\TestContainerBuilder;

class HandleFieldsFilterTest extends GetProcessorTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ValueNormalizer */
    private $valueNormalizer;

    /** @var HandleFieldsFilter */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->valueNormalizer = $this->createMock(ValueNormalizer::class);

        $filterNames = $this->createMock(FilterNames::class);
        $filterNames->expects(self::any())
            ->method('getFieldsFilterGroupName')
            ->willReturn('fields');

        $this->processor = new HandleFieldsFilter(
            new FilterNamesRegistry(
                [['filter_names', null]],
                TestContainerBuilder::create()->add('filter_names', $filterNames)->getContainer($this),
                new RequestExpressionMatcher()
            ),
            $this->valueNormalizer
        );
    }

    public function testProcessWhenNoFieldsFilterAlreadyHandled()
    {
        $configExtra = new FilterFieldsConfigExtra([]);
        $this->context->addConfigExtra($configExtra);
        $this->processor->process($this->context);

        self::assertSame($configExtra, $this->context->getConfigExtra(FilterFieldsConfigExtra::NAME));
    }

    public function testProcessWhenNoFieldsFilterValue()
    {
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasConfigExtra(FilterFieldsConfigExtra::NAME));
    }

    public function testProcessForFieldsFilterWithEmptyValue()
    {
        $filterValue = FilterValue::createFromSource('fields[entity]', 'entity', '');

        $this->valueNormalizer->expects(self::never())
            ->method('normalizeValue');

        $this->context->getFilterValues()->set('fields[entity]', $filterValue);
        $this->processor->process($this->context);

        self::assertEquals(
            new FilterFieldsConfigExtra([
                'entity' => []
            ]),
            $this->context->getConfigExtra(FilterFieldsConfigExtra::NAME)
        );
    }

    public function testProcessForFieldsFilter()
    {
        $filterValue = FilterValue::createFromSource('fields[entity]', 'entity', 'field1');

        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with('field1', DataType::STRING, self::identicalTo($this->context->getRequestType()), self::isTrue())
            ->willReturn('field1');

        $this->context->getFilterValues()->set('fields[entity]', $filterValue);
        $this->processor->process($this->context);

        self::assertEquals(
            new FilterFieldsConfigExtra([
                'entity' => ['field1']
            ]),
            $this->context->getConfigExtra(FilterFieldsConfigExtra::NAME)
        );
    }

    public function testProcessForWrongFieldsFilterValue()
    {
        $filterValue = FilterValue::createFromSource('fields[entity]', 'entity', 'field1');

        $exception = new \Exception('some error');
        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with('field1', DataType::STRING, self::identicalTo($this->context->getRequestType()), self::isTrue())
            ->willThrowException($exception);

        $this->context->getFilterValues()->set('fields[entity]', $filterValue);
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasConfigExtra(FilterFieldsConfigExtra::NAME));
        self::assertEquals(
            [
                Error::createValidationError(Constraint::FILTER)
                    ->setInnerException($exception)
                    ->setSource(ErrorSource::createByParameter($filterValue->getSourceKey()))
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessForFieldsFilterValueWithEmptyEntityType()
    {
        $filterValue = FilterValue::createFromSource('fields[]', '', 'field1');

        $this->valueNormalizer->expects(self::never())
            ->method('normalizeValue');

        $this->context->getFilterValues()->set('fields[]', $filterValue);
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasConfigExtra(FilterFieldsConfigExtra::NAME));
        self::assertEquals(
            [
                Error::createValidationError(Constraint::FILTER)
                    ->setDetail('An entity type is not specified.')
                    ->setSource(ErrorSource::createByParameter($filterValue->getSourceKey()))
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessForFieldsFilterValueWithEmptyEntityTypeBecauseItIsSpecifiedAsNotGroupedFilter()
    {
        $filterValue = FilterValue::createFromSource('fields', 'fields', 'field1');

        $this->valueNormalizer->expects(self::never())
            ->method('normalizeValue');

        $this->context->getFilterValues()->set('fields', $filterValue);
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasConfigExtra(FilterFieldsConfigExtra::NAME));
        self::assertEquals(
            [
                Error::createValidationError(Constraint::FILTER)
                    ->setDetail('An entity type is not specified.')
                    ->setSource(ErrorSource::createByParameter($filterValue->getSourceKey()))
            ],
            $this->context->getErrors()
        );
    }
}
