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
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get\GetProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Oro\Bundle\EntityBundle\Exception\EntityAliasNotFoundException;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use PHPUnit\Framework\MockObject\MockObject;

class HandleFieldsFilterTest extends GetProcessorTestCase
{
    private ValueNormalizer&MockObject $valueNormalizer;
    private FilterNames&MockObject $filterNames;
    private HandleFieldsFilter $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->valueNormalizer = $this->createMock(ValueNormalizer::class);

        $this->filterNames = $this->createMock(FilterNames::class);
        $this->filterNames->expects(self::any())
            ->method('getFieldsFilterGroupName')
            ->willReturn('fields');

        $this->processor = new HandleFieldsFilter(
            new FilterNamesRegistry(
                [['filter_names', null]],
                TestContainerBuilder::create()->add('filter_names', $this->filterNames)->getContainer($this),
                new RequestExpressionMatcher()
            ),
            $this->valueNormalizer
        );
    }

    public function testProcessWhenNoFieldsFilterAlreadyHandled(): void
    {
        $configExtra = new FilterFieldsConfigExtra([]);
        $this->context->addConfigExtra($configExtra);
        $this->context->setClassName('Test\PrimaryEntity');
        $this->processor->process($this->context);

        self::assertSame($configExtra, $this->context->getConfigExtra(FilterFieldsConfigExtra::NAME));
    }

    public function testProcessWhenNoFieldsFilterValue(): void
    {
        $this->context->setClassName('Test\PrimaryEntity');
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasConfigExtra(FilterFieldsConfigExtra::NAME));
    }

    public function testProcessForFieldsFilterWithEmptyValue(): void
    {
        $filterValue = FilterValue::createFromSource('fields[entity]', 'entity', '');

        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with('entity', DataType::ENTITY_CLASS, $this->context->getRequestType())
            ->willReturn('Test\Entity');

        $this->filterNames->expects(self::once())
            ->method('getFieldsFilterTemplate')
            ->willReturn('fields[%s]');

        $this->context->getFilterValues()->set('fields[entity]', $filterValue);
        $this->context->setClassName('Test\PrimaryEntity');
        $this->processor->process($this->context);

        self::assertEquals(
            new FilterFieldsConfigExtra([
                'entity' => []
            ]),
            $this->context->getConfigExtra(FilterFieldsConfigExtra::NAME)
        );
    }

    public function testProcessForFieldsFilter(): void
    {
        $filterValue = FilterValue::createFromSource('fields[entity]', 'entity', 'field1');

        $requestType = $this->context->getRequestType();
        $this->valueNormalizer->expects(self::exactly(2))
            ->method('normalizeValue')
            ->willReturnMap([
                ['entity', DataType::ENTITY_CLASS, $requestType, false, false, [], 'Test\Entity'],
                ['field1', DataType::STRING, $requestType, true, false, [], 'field1']
            ]);

        $this->filterNames->expects(self::once())
            ->method('getFieldsFilterTemplate')
            ->willReturn('fields[%s]');

        $this->context->getFilterValues()->set('fields[entity]', $filterValue);
        $this->context->setClassName('Test\PrimaryEntity');
        $this->processor->process($this->context);

        self::assertEquals(
            new FilterFieldsConfigExtra([
                'entity' => ['field1']
            ]),
            $this->context->getConfigExtra(FilterFieldsConfigExtra::NAME)
        );
    }

    public function testProcessForUnknownEntityType(): void
    {
        $filterValue = FilterValue::createFromSource('fields[entity]', 'entity', 'field1');

        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with('entity', DataType::ENTITY_CLASS, $this->context->getRequestType())
            ->willThrowException(new EntityAliasNotFoundException('unknown entity type'));

        $this->filterNames->expects(self::once())
            ->method('getFieldsFilterTemplate')
            ->willReturn('fields[%s]');

        $this->context->getFilterValues()->set('fields[entity]', $filterValue);
        $this->context->setClassName('Test\PrimaryEntity');
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasConfigExtra(FilterFieldsConfigExtra::NAME));
        self::assertEquals(
            [
                Error::createValidationError(Constraint::FILTER)
                    ->setDetail('An entity type is not known.')
                    ->setSource(ErrorSource::createByParameter($filterValue->getSourceKey()))
            ],
            $this->context->getErrors()
        );
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function testProcessForWrongFieldsFilterValue(): void
    {
        $filterValue = FilterValue::createFromSource('fields[entity]', 'entity', 'field1');

        $exception = new \Exception('some error');
        $this->valueNormalizer->expects(self::exactly(2))
            ->method('normalizeValue')
            ->willReturnCallback(
                function (
                    mixed $value,
                    string $dataType,
                    RequestType $requestType,
                    bool $isArrayAllowed,
                    bool $isRangeAllowed
                ) use ($exception) {
                    if ('entity' === $value
                        && DataType::ENTITY_CLASS === $dataType
                        && $this->context->getRequestType() === $requestType
                        && !$isArrayAllowed
                        && !$isRangeAllowed
                    ) {
                        return 'Test\Entity';
                    }
                    if ('field1' === $value
                        && DataType::STRING === $dataType
                        && $this->context->getRequestType() === $requestType
                        && $isArrayAllowed
                        && !$isRangeAllowed
                    ) {
                        throw $exception;
                    }
                    throw new \LogicException('Unexpected arguments.');
                }
            );

        $this->filterNames->expects(self::once())
            ->method('getFieldsFilterTemplate')
            ->willReturn('fields[%s]');

        $this->context->getFilterValues()->set('fields[entity]', $filterValue);
        $this->context->setClassName('Test\PrimaryEntity');
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

    public function testProcessForFieldsFilterValueWithEmptyEntityType(): void
    {
        $filterValue = FilterValue::createFromSource('fields[]', '', 'field1');

        $this->valueNormalizer->expects(self::never())
            ->method('normalizeValue');

        $this->filterNames->expects(self::once())
            ->method('getFieldsFilterTemplate')
            ->willReturn('fields[%s]');

        $this->context->getFilterValues()->set('fields[]', $filterValue);
        $this->context->setClassName('Test\PrimaryEntity');
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

    public function testProcessForFieldsFilterValueWithEmptyEntityTypeBecauseItIsSpecifiedAsNotGroupedFilter(): void
    {
        $filterValue = FilterValue::createFromSource('fields', 'fields', 'field1');

        $this->valueNormalizer->expects(self::never())
            ->method('normalizeValue');

        $this->filterNames->expects(self::once())
            ->method('getFieldsFilterTemplate')
            ->willReturn('fields[%s]');

        $this->context->getFilterValues()->set('fields', $filterValue);
        $this->context->setClassName('Test\PrimaryEntity');
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

    public function testProcessForFieldsFilterWhenItIsApplicableToPrimaryEntityOnly(): void
    {
        $filterValue = FilterValue::createFromSource('fields', 'fields', 'field1');

        $requestType = $this->context->getRequestType();
        $this->valueNormalizer->expects(self::exactly(3))
            ->method('normalizeValue')
            ->willReturnMap([
                ['Test\PrimaryEntity', DataType::ENTITY_TYPE, $requestType, false, false, [], 'primaryEntity'],
                ['primaryEntity', DataType::ENTITY_CLASS, $requestType, false, false, [], 'Test\PrimaryEntity'],
                ['field1', DataType::STRING, $requestType, true, false, [], 'field1']
            ]);

        $this->filterNames->expects(self::once())
            ->method('getFieldsFilterTemplate')
            ->willReturn(null);

        $this->context->getFilterValues()->set('fields[entity]', $filterValue);
        $this->context->setClassName('Test\PrimaryEntity');
        $this->processor->process($this->context);

        self::assertEquals(
            new FilterFieldsConfigExtra([
                'primaryEntity' => ['field1']
            ]),
            $this->context->getConfigExtra(FilterFieldsConfigExtra::NAME)
        );
    }

    public function testProcessForGroupedFieldsFilterValueWhenFieldsFilterIsApplicableToPrimaryEntityOnly(): void
    {
        $filterValue = FilterValue::createFromSource('fields[entity]', 'entity', 'field1');

        $this->valueNormalizer->expects(self::never())
            ->method('normalizeValue');

        $this->filterNames->expects(self::once())
            ->method('getFieldsFilterTemplate')
            ->willReturn(null);

        $this->context->getFilterValues()->set('fields[]', $filterValue);
        $this->context->setClassName('Test\PrimaryEntity');
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasConfigExtra(FilterFieldsConfigExtra::NAME));
        self::assertEquals(
            [
                Error::createValidationError(Constraint::FILTER)
                    ->setDetail('The filter is not supported.')
                    ->setSource(ErrorSource::createByParameter($filterValue->getSourceKey()))
            ],
            $this->context->getErrors()
        );
    }
}
