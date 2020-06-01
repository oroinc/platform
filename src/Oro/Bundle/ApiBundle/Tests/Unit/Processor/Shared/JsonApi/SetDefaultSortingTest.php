<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared\JsonApi;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\SortersConfig;
use Oro\Bundle\ApiBundle\Filter\FilterNames;
use Oro\Bundle\ApiBundle\Filter\FilterNamesRegistry;
use Oro\Bundle\ApiBundle\Filter\SortFilter;
use Oro\Bundle\ApiBundle\Processor\Shared\JsonApi\SetDefaultSorting;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Category;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Oro\Component\Testing\Unit\TestContainerBuilder;

class SetDefaultSortingTest extends GetListProcessorTestCase
{
    /** @var SetDefaultSorting */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $filterNames = $this->createMock(FilterNames::class);
        $filterNames->expects(self::any())
            ->method('getSortFilterName')
            ->willReturn('sort');

        $this->processor = new SetDefaultSorting(
            new FilterNamesRegistry(
                [['filter_names', null]],
                TestContainerBuilder::create()->add('filter_names', $filterNames)->getContainer($this),
                new RequestExpressionMatcher()
            )
        );
    }

    public function testProcessWhenQueryIsAlreadyExist()
    {
        $query = new \stdClass();

        $this->context->setQuery($query);
        $this->processor->process($this->context);

        self::assertSame($query, $this->context->getQuery());
    }

    public function testProcessWhenSortFilterIsNotAddedYet()
    {
        $config = new EntityDefinitionConfig();
        $config->setIdentifierFieldNames(['name']);
        $config->addField('name');

        $configOfSorters = new SortersConfig();
        $configOfSorters->addField('name');

        $this->context->getRequestType()->add(RequestType::JSON_API);
        $this->context->setClassName(Category::class);
        $this->context->setConfig($config);
        $this->context->setConfigOfSorters($configOfSorters);
        $this->processor->process($this->context);

        $filters = $this->context->getFilters();
        self::assertCount(1, $filters);
        /** @var SortFilter $sortFilter */
        $sortFilter = $filters->get('sort');
        self::assertEquals('orderBy', $sortFilter->getDataType());
        self::assertEquals(['id' => 'ASC'], $sortFilter->getDefaultValue());
        self::assertFalse($filters->isIncludeInDefaultGroup('sort'));
    }

    public function testProcessWhenSortFilterIsNotAddedYetAndSorterIsDisabled()
    {
        $config = new EntityDefinitionConfig();
        $config->setIdentifierFieldNames(['name']);
        $config->addField('name');

        $configOfSorters = new SortersConfig();
        $configOfSorters->addField('name')->setExcluded(true);

        $this->context->getRequestType()->add(RequestType::JSON_API);
        $this->context->setClassName(Category::class);
        $this->context->setConfig($config);
        $this->context->setConfigOfSorters($configOfSorters);
        $this->processor->process($this->context);

        $filters = $this->context->getFilters();
        self::assertCount(1, $filters);
        /** @var SortFilter $sortFilter */
        $sortFilter = $filters->get('sort');
        self::assertEquals('orderBy', $sortFilter->getDataType());
        self::assertSame([], $sortFilter->getDefaultValue());
        self::assertFalse($filters->isIncludeInDefaultGroup('sort'));
    }

    public function testProcessWhenSortFilterIsNotAddedYetForRenamedIdentifierField()
    {
        $config = new EntityDefinitionConfig();
        $config->setIdentifierFieldNames(['renamedName']);
        $config->addField('renamedName')->setPropertyPath('name');

        $configOfSorters = new SortersConfig();
        $configOfSorters->addField('renamedName')->setPropertyPath('name');

        $this->context->getRequestType()->add(RequestType::JSON_API);
        $this->context->setClassName(Category::class);
        $this->context->setConfig($config);
        $this->context->setConfigOfSorters($configOfSorters);
        $this->processor->process($this->context);

        $filters = $this->context->getFilters();
        self::assertCount(1, $filters);
        /** @var SortFilter $sortFilter */
        $sortFilter = $filters->get('sort');
        self::assertEquals('orderBy', $sortFilter->getDataType());
        self::assertEquals(['id' => 'ASC'], $sortFilter->getDefaultValue());
        self::assertFalse($filters->isIncludeInDefaultGroup('sort'));
    }

    public function testProcessWhenSortFilterIsNotAddedYetForRenamedIdentifierFieldAndSorterIsDisabled()
    {
        $config = new EntityDefinitionConfig();
        $config->setIdentifierFieldNames(['renamedName']);
        $config->addField('renamedName')->setPropertyPath('name');

        $configOfSorters = new SortersConfig();
        $sorterField = $configOfSorters->addField('renamedName');
        $sorterField->setPropertyPath('name');
        $sorterField->setExcluded(true);

        $this->context->getRequestType()->add(RequestType::JSON_API);
        $this->context->setClassName(Category::class);
        $this->context->setConfig($config);
        $this->context->setConfigOfSorters($configOfSorters);
        $this->processor->process($this->context);

        $filters = $this->context->getFilters();
        self::assertCount(1, $filters);
        /** @var SortFilter $sortFilter */
        $sortFilter = $filters->get('sort');
        self::assertEquals('orderBy', $sortFilter->getDataType());
        self::assertSame([], $sortFilter->getDefaultValue());
        self::assertFalse($filters->isIncludeInDefaultGroup('sort'));
    }

    public function testProcessWhenConfigHasOrderByOption()
    {
        $config = new EntityDefinitionConfig();
        $config->setIdentifierFieldNames(['name']);
        $config->setOrderBy(['label' => 'DESC']);

        $configOfSorters = new SortersConfig();
        $configOfSorters->addField('name');

        $this->context->getRequestType()->add(RequestType::JSON_API);
        $this->context->setClassName(Category::class);
        $this->context->setConfig($config);
        $this->context->setConfigOfSorters($configOfSorters);
        $this->processor->process($this->context);

        $filters = $this->context->getFilters();
        self::assertCount(1, $filters);
        /** @var SortFilter $sortFilter */
        $sortFilter = $filters->get('sort');
        self::assertEquals('orderBy', $sortFilter->getDataType());
        self::assertEquals(['label' => 'DESC'], $sortFilter->getDefaultValue());
        self::assertFalse($filters->isIncludeInDefaultGroup('sort'));
    }

    public function testProcessForEntityWithoutIdentifier()
    {
        $this->context->getRequestType()->add(RequestType::JSON_API);
        $this->context->setClassName(Category::class);
        $this->context->setConfig(new EntityDefinitionConfig());
        $this->context->setConfigOfSorters(new SortersConfig());
        $this->processor->process($this->context);

        $filters = $this->context->getFilters();
        self::assertCount(1, $filters);
        /** @var SortFilter $sortFilter */
        $sortFilter = $filters->get('sort');
        self::assertEquals('orderBy', $sortFilter->getDataType());
        self::assertEquals([], $sortFilter->getDefaultValue());
        self::assertFalse($filters->isIncludeInDefaultGroup('sort'));
    }

    public function testProcessWhenSortFilterIsAlreadyAdded()
    {
        $sortFilter = new SortFilter(DataType::ORDER_BY);

        $this->context->getRequestType()->add(RequestType::JSON_API);
        $this->context->setClassName(Category::class);
        $this->context->setConfig(new EntityDefinitionConfig());
        $this->context->setConfigOfSorters(new SortersConfig());
        $this->context->getFilters()->add('sort', $sortFilter, false);
        $this->processor->process($this->context);

        self::assertSame($sortFilter, $this->context->getFilters()->get('sort'));
        self::assertFalse($this->context->getFilters()->isIncludeInDefaultGroup('sort'));
    }

    public function testProcessWhenSortingIsDisabled()
    {
        $config = new EntityDefinitionConfig();
        $config->disableSorting();

        $this->context->getRequestType()->add(RequestType::JSON_API);
        $this->context->setConfig($config);
        $this->processor->process($this->context);

        self::assertCount(0, $this->context->getFilters());
    }
}
