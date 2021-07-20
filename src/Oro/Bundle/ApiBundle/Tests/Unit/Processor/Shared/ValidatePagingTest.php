<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Filter\FilterNames;
use Oro\Bundle\ApiBundle\Filter\FilterNamesRegistry;
use Oro\Bundle\ApiBundle\Filter\FilterValue;
use Oro\Bundle\ApiBundle\Filter\PageSizeFilter;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\Shared\ValidatePaging;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Oro\Component\Testing\Unit\TestContainerBuilder;

class ValidatePagingTest extends GetListProcessorTestCase
{
    private function getProcessor(int $maxEntitiesLimit): ValidatePaging
    {
        $filterNames = $this->createMock(FilterNames::class);
        $filterNames->expects(self::any())
            ->method('getPageSizeFilterName')
            ->willReturn('page[size]');

        return new ValidatePaging(
            new FilterNamesRegistry(
                [['filter_names', null]],
                TestContainerBuilder::create()->add('filter_names', $filterNames)->getContainer($this),
                new RequestExpressionMatcher()
            ),
            $maxEntitiesLimit
        );
    }

    public function testProcessWhenQueryIsAlreadyBuilt()
    {
        $query = new \stdClass();

        $this->context->setQuery($query);
        $processor = $this->getProcessor(-1);
        $processor->process($this->context);

        self::assertSame($query, $this->context->getQuery());
        self::assertFalse($this->context->hasErrors());
    }

    public function testProcessWhenPageSizeFilterIsNotSupported()
    {
        $this->context->setConfig(new EntityDefinitionConfig());
        $processor = $this->getProcessor(9);
        $processor->process($this->context);

        self::assertFalse($this->context->hasErrors());
    }

    public function testProcessWhenPageSizeIsNotRequested()
    {
        $this->context->getFilters()->add('page[size]', new PageSizeFilter(DataType::INTEGER), false);

        $this->context->setConfig(new EntityDefinitionConfig());
        $processor = $this->getProcessor(9);
        $processor->process($this->context);

        self::assertFalse($this->context->hasErrors());
    }

    public function testProcessWhenUnlimitedMaxResults()
    {
        $this->context->getFilters()->add('page[size]', new PageSizeFilter(DataType::INTEGER), false);
        $this->context->getFilterValues()->set('page[size]', new FilterValue('page[size]', 10));

        $this->context->setConfig(new EntityDefinitionConfig());
        $processor = $this->getProcessor(-1);
        $processor->process($this->context);

        self::assertFalse($this->context->hasErrors());
    }

    public function testProcessWhenPageSizeLessThanMaxResults()
    {
        $this->context->getFilters()->add('page[size]', new PageSizeFilter(DataType::INTEGER), false);
        $this->context->getFilterValues()->set('page[size]', new FilterValue('page[size]', 10));

        $this->context->setConfig(new EntityDefinitionConfig());
        $processor = $this->getProcessor(11);
        $processor->process($this->context);

        self::assertFalse($this->context->hasErrors());
    }

    public function testProcessWhenPageSizeEqualsToMaxResults()
    {
        $this->context->getFilters()->add('page[size]', new PageSizeFilter(DataType::INTEGER), false);
        $this->context->getFilterValues()->set('page[size]', new FilterValue('page[size]', 10));

        $this->context->setConfig(new EntityDefinitionConfig());
        $processor = $this->getProcessor(10);
        $processor->process($this->context);

        self::assertFalse($this->context->hasErrors());
    }

    public function testProcessWhenPageSizeGreaterThanMaxResults()
    {
        $this->context->getFilters()->add('page[size]', new PageSizeFilter(DataType::INTEGER), false);
        $this->context->getFilterValues()->set('page[size]', new FilterValue('page[size]', 10));

        $this->context->setConfig(new EntityDefinitionConfig());
        $processor = $this->getProcessor(9);
        $processor->process($this->context);

        self::assertEquals(
            [
                Error::createValidationError(Constraint::FILTER, 'The value should be less than or equals to 9.')
                    ->setSource(ErrorSource::createByParameter('page[size]'))
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWhenPageSizeGreaterThanMaxResultsInConfig()
    {
        $this->context->getFilters()->add('page[size]', new PageSizeFilter(DataType::INTEGER), false);
        $this->context->getFilterValues()->set('page[size]', new FilterValue('page[size]', 10));

        $config = new EntityDefinitionConfig();
        $config->setMaxResults(9);

        $this->context->setConfig($config);
        $processor = $this->getProcessor(10);
        $processor->process($this->context);

        self::assertEquals(
            [
                Error::createValidationError(Constraint::FILTER, 'The value should be less than or equals to 9.')
                    ->setSource(ErrorSource::createByParameter('page[size]'))
            ],
            $this->context->getErrors()
        );
    }
}
