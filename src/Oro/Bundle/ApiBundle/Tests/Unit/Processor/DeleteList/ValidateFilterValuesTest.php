<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\DeleteList;

use Oro\Bundle\ApiBundle\Filter\ComparisonFilter;
use Oro\Bundle\ApiBundle\Filter\FilterValue;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\DeleteList\ValidateFilterValues;

class ValidateFilterValuesTest extends DeleteListProcessorTestCase
{
    private ValidateFilterValues $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = new ValidateFilterValues();
    }

    public function testProcessWhenQueryIsAlreadyBuilt(): void
    {
        $this->context->setQuery(new \stdClass());
        $context = clone $this->context;
        $this->processor->process($this->context);
        self::assertEquals($context, $this->context);
    }

    public function testProcess(): void
    {
        $filters = $this->context->getFilters();
        $integerFilter = new ComparisonFilter('integer');
        $stringFilter = new ComparisonFilter('string');
        $filters->add('id', $integerFilter);
        $filters->add('label', $stringFilter);

        $this->context->getFilterValues()->set('id', new FilterValue('id', '1'));
        $this->context->getFilterValues()->set('label', new FilterValue('label', 'test'));
        $this->processor->process($this->context);
    }

    public function testProcessWhenNoFilters(): void
    {
        $filters = $this->context->getFilters();
        $integerFilter = new ComparisonFilter('integer');
        $stringFilter = new ComparisonFilter('string');
        $filters->add('id', $integerFilter);
        $filters->add('label', $stringFilter);

        $this->processor->process($this->context);

        self::assertEquals(
            [
                Error::createValidationError('filter constraint', 'At least one filter must be provided.')
            ],
            $this->context->getErrors()
        );
    }
}
