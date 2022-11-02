<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetConfig;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\FiltersConfig;
use Oro\Bundle\ApiBundle\Processor\GetConfig\CompleteCompositeIdentifierFilter;
use Oro\Bundle\ApiBundle\Request\DataType;

class CompleteCompositeIdentifierFilterTest extends ConfigProcessorTestCase
{
    private const IDENTIFIER_FILTER_NAME = 'id';

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
        $filter = $filters->addField(self::IDENTIFIER_FILTER_NAME);
        $filter->setType('custom_filter');

        $this->context->setResult($definition);
        $this->context->setFilters($filters);
        $this->processor->process($this->context);

        self::assertEquals('custom_filter', $filters->getField(self::IDENTIFIER_FILTER_NAME)->getType());
    }

    public function testProcessWhenIdFilterDoesNotExist()
    {
        $definition = new EntityDefinitionConfig();
        $definition->setIdentifierFieldNames(['id1', 'id2']);
        $filters = new FiltersConfig();

        $this->context->setResult($definition);
        $this->context->setFilters($filters);
        $this->processor->process($this->context);

        $filter = $filters->getField(self::IDENTIFIER_FILTER_NAME);
        self::assertNotNull($filter);
        self::assertEquals('composite_identifier', $filter->getType());
        self::assertEquals(DataType::STRING, $filter->getDataType());
        self::assertTrue($filter->isArrayAllowed());
    }

    public function testProcessForAssociationWithSingleIdentifier(): void
    {
        $definition = new EntityDefinitionConfig();
        $definition->addField('field');
        $association = $definition->addField('association');
        $association->setTargetClass('Test\TargetEntity');
        $associationTargetEntity = $association->createAndSetTargetEntity();
        $associationTargetEntity->setIdentifierFieldNames(['id']);
        $filters = new FiltersConfig();

        $this->context->setResult($definition);
        $this->context->setFilters($filters);
        $this->processor->process($this->context);

        self::assertFalse($this->context->getFilters()->hasField('field'));
        self::assertFalse($this->context->getFilters()->hasField('association'));
    }

    public function testProcessForAssociationWithCompositeIdentifier(): void
    {
        $definition = new EntityDefinitionConfig();
        $definition->addField('field');
        $association = $definition->addField('association');
        $association->setTargetClass('Test\TargetEntity');
        $associationTargetEntity = $association->createAndSetTargetEntity();
        $associationTargetEntity->setIdentifierFieldNames(['id1', 'id2']);
        $filters = new FiltersConfig();

        $this->context->setResult($definition);
        $this->context->setFilters($filters);
        $this->processor->process($this->context);

        self::assertFalse($this->context->getFilters()->hasField('field'));
        $filter = $this->context->getFilters()->getField('association');
        self::assertNotNull($filter);
        self::assertEquals('association_composite_identifier', $filter->getType());
        self::assertEquals(DataType::STRING, $filter->getDataType());
        self::assertTrue($filter->isArrayAllowed());
    }

    public function testProcessForExcludedAssociationWithCompositeIdentifier(): void
    {
        $definition = new EntityDefinitionConfig();
        $definition->addField('field');
        $association = $definition->addField('association');
        $association->setExcluded();
        $association->setTargetClass('Test\TargetEntity');
        $associationTargetEntity = $association->createAndSetTargetEntity();
        $associationTargetEntity->setIdentifierFieldNames(['id1', 'id2']);
        $filters = new FiltersConfig();

        $this->context->setResult($definition);
        $this->context->setFilters($filters);
        $this->processor->process($this->context);

        self::assertFalse($this->context->getFilters()->hasField('field'));
        self::assertFalse($this->context->getFilters()->hasField('association'));
    }

    public function testProcessForAssociationWithCompositeIdentifierWhenFilterAlreadyExists(): void
    {
        $definition = new EntityDefinitionConfig();
        $definition->addField('field');
        $association = $definition->addField('association');
        $association->setTargetClass('Test\TargetEntity');
        $associationTargetEntity = $association->createAndSetTargetEntity();
        $associationTargetEntity->setIdentifierFieldNames(['id1', 'id2']);
        $filters = new FiltersConfig();
        $associationFilter = $filters->addField('association');
        $associationFilter->setType('custom_filter');

        $this->context->setResult($definition);
        $this->context->setFilters($filters);
        $this->processor->process($this->context);

        self::assertFalse($this->context->getFilters()->hasField('field'));
        self::assertEquals('custom_filter', $filters->getField('association')->getType());
    }
}
