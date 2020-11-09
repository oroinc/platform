<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Config\Extra\MetaPropertiesConfigExtra;
use Oro\Bundle\ApiBundle\Filter\FilterNames;
use Oro\Bundle\ApiBundle\Filter\FilterNamesRegistry;
use Oro\Bundle\ApiBundle\Filter\FilterValue;
use Oro\Bundle\ApiBundle\Filter\MetaPropertyFilter;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\Shared\HandleMetaPropertyFilter;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get\GetProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Oro\Component\Testing\Unit\TestContainerBuilder;

class HandleMetaPropertyFilterTest extends GetProcessorTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ValueNormalizer */
    private $valueNormalizer;

    /** @var HandleMetaPropertyFilter */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->valueNormalizer = $this->createMock(ValueNormalizer::class);

        $filterNames = $this->createMock(FilterNames::class);
        $filterNames->expects(self::any())
            ->method('getMetaPropertyFilterName')
            ->willReturn('meta');

        $this->processor = new HandleMetaPropertyFilter(
            new FilterNamesRegistry(
                [['filter_names', null]],
                TestContainerBuilder::create()->add('filter_names', $filterNames)->getContainer($this),
                new RequestExpressionMatcher()
            ),
            $this->valueNormalizer
        );
    }

    public function testProcessWhenNoMetaFilterValue()
    {
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasConfigExtra(MetaPropertiesConfigExtra::NAME));
    }

    public function testProcessWhenNoMetaFilter()
    {
        $filterValue = FilterValue::createFromSource('meta', 'meta', 'test');

        $this->context->getFilterValues()->set('meta', $filterValue);
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasConfigExtra(MetaPropertiesConfigExtra::NAME));
    }

    public function testProcessWhenMetaFilterValueExists()
    {
        $filterValue = FilterValue::createFromSource('meta', 'meta', 'test1,test2');
        $filter = new MetaPropertyFilter('string');
        $filter->addAllowedMetaProperty('test1', 'string');
        $filter->addAllowedMetaProperty('test2', null);

        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with('test1,test2', DataType::STRING, $this->context->getRequestType(), true)
            ->willReturn(['test1', 'test2']);

        $this->context->getFilterValues()->set('meta', $filterValue);
        $this->context->getFilters()->set('meta', $filter);
        $this->processor->process($this->context);

        $expectedConfigExtra = new MetaPropertiesConfigExtra();
        $expectedConfigExtra->addMetaProperty('test1', 'string');
        $expectedConfigExtra->addMetaProperty('test2', null);

        self::assertEquals(
            $expectedConfigExtra,
            $this->context->getConfigExtra(MetaPropertiesConfigExtra::NAME)
        );
        self::assertFalse($this->context->hasErrors());
    }

    public function testProcessWhenMetaFilterHasInvalidValue()
    {
        $filterValue = FilterValue::createFromSource('meta', 'meta', 'test1,');
        $filter = new MetaPropertyFilter('string');
        $filter->addAllowedMetaProperty('test2', 'string');
        $filter->addAllowedMetaProperty('test3', null);

        $exception = new \UnexpectedValueException('invalid value');
        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with('test1,', DataType::STRING, $this->context->getRequestType(), true)
            ->willThrowException($exception);

        $this->context->getFilterValues()->set('meta', $filterValue);
        $this->context->getFilters()->set('meta', $filter);
        $this->processor->process($this->context);

        $expectedErrors = [];
        $expectedErrors[] =
            Error::createValidationError(Constraint::FILTER)
                ->setInnerException($exception)
                ->setSource(ErrorSource::createByParameter('meta'));

        self::assertNull($this->context->getConfigExtra(MetaPropertiesConfigExtra::NAME));
        self::assertEquals($expectedErrors, $this->context->getErrors());
    }

    public function testProcessWhenNotAllowedMetaPropertyIsRequested()
    {
        $filterValue = FilterValue::createFromSource('meta', 'meta', 'test1,test2');
        $filter = new MetaPropertyFilter('string');
        $filter->addAllowedMetaProperty('test2', 'string');
        $filter->addAllowedMetaProperty('test3', null);

        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with('test1,test2', DataType::STRING, $this->context->getRequestType(), true)
            ->willReturn(['test1', 'test2']);

        $this->context->getFilterValues()->set('meta', $filterValue);
        $this->context->getFilters()->set('meta', $filter);
        $this->processor->process($this->context);

        $expectedConfigExtra = new MetaPropertiesConfigExtra();
        $expectedConfigExtra->addMetaProperty('test2', 'string');

        $expectedErrors = [];
        $expectedErrors[] =
            Error::createValidationError(
                Constraint::FILTER,
                'The "test1" value is not allowed. Allowed values: test2, test3'
            )
            ->setSource(ErrorSource::createByParameter('meta'));

        self::assertEquals(
            $expectedConfigExtra,
            $this->context->getConfigExtra(MetaPropertiesConfigExtra::NAME)
        );
        self::assertEquals($expectedErrors, $this->context->getErrors());
    }
}
