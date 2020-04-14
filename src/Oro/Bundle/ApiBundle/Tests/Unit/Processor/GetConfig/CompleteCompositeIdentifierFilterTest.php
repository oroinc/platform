<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetConfig;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\FiltersConfig;
use Oro\Bundle\ApiBundle\Processor\GetConfig\CompleteCompositeIdentifierFilter;
use Oro\Bundle\ApiBundle\Request\DataType;

class CompleteCompositeIdentifierFilterTest extends ConfigProcessorTestCase
{
    /** @var CompleteCompositeIdentifierFilter */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = new CompleteCompositeIdentifierFilter();
    }

    public function testProcessForEntityWithSingleIdentifier()
    {
        $definition = new EntityDefinitionConfig();
        $definition->setIdentifierFieldNames(['id']);

        $this->context->setResult($definition);
        $this->processor->process($this->context);
    }

    public function testProcessWhenIdFilterAlreadyExists()
    {
        $definition = new EntityDefinitionConfig();
        $definition->setIdentifierFieldNames(['id1', 'id2']);
        $filters = new FiltersConfig();
        $filter = $filters->addField(CompleteCompositeIdentifierFilter::IDENTIFIER_FILTER_NAME);
        $filter->setType('custom_filter');

        $this->context->setResult($definition);
        $this->context->setFilters($filters);
        $this->processor->process($this->context);

        self::assertEquals(
            'custom_filter',
            $filters->getField(CompleteCompositeIdentifierFilter::IDENTIFIER_FILTER_NAME)->getType()
        );
    }

    public function testProcessWhenIdFilterDoesNotExist()
    {
        $definition = new EntityDefinitionConfig();
        $definition->setIdentifierFieldNames(['id1', 'id2']);
        $filters = new FiltersConfig();

        $this->context->setResult($definition);
        $this->context->setFilters($filters);
        $this->processor->process($this->context);

        $filter = $filters->getField(CompleteCompositeIdentifierFilter::IDENTIFIER_FILTER_NAME);
        self::assertNotNull($filter);
        self::assertEquals('composite_identifier', $filter->getType());
        self::assertEquals(DataType::STRING, $filter->getDataType());
        self::assertTrue($filter->isArrayAllowed());
    }
}
