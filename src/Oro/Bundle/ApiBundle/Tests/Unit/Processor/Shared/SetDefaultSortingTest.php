<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\SortersConfig;
use Oro\Bundle\ApiBundle\Filter\FilterNames;
use Oro\Bundle\ApiBundle\Filter\FilterNamesRegistry;
use Oro\Bundle\ApiBundle\Filter\SortFilter;
use Oro\Bundle\ApiBundle\Processor\Shared\SetDefaultSorting;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Category;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\CompositeKeyEntity;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class SetDefaultSortingTest extends GetListProcessorTestCase
{
    private FilterNames&MockObject $filterNames;
    private SetDefaultSorting $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->filterNames = $this->createMock(FilterNames::class);

        $this->processor = new SetDefaultSorting(
            new FilterNamesRegistry(
                [['filter_names', null]],
                TestContainerBuilder::create()->add('filter_names', $this->filterNames)->getContainer($this),
                new RequestExpressionMatcher()
            )
        );
    }

    public function testProcessWhenQueryIsAlreadyExist(): void
    {
        $query = new \stdClass();

        $this->context->setQuery($query);
        $this->processor->process($this->context);

        self::assertSame($query, $this->context->getQuery());
    }

    public function testProcessWhenSortingIsNotSupported(): void
    {
        $config = new EntityDefinitionConfig();
        $config->setIdentifierFieldNames(['id']);
        $config->addField('id');

        $configOfSorters = new SortersConfig();
        $configOfSorters->addField('id');

        $this->filterNames->expects(self::once())
            ->method('getSortFilterName')
            ->willReturn(null);

        $this->context->setClassName(User::class);
        $this->context->setConfig($config);
        $this->context->setConfigOfSorters($configOfSorters);
        $this->processor->process($this->context);

        self::assertCount(0, $this->context->getFilters());
    }

    public function testProcessForEntityWithIdentifierNamedId(): void
    {
        $config = new EntityDefinitionConfig();
        $config->setIdentifierFieldNames(['id']);
        $config->addField('id');

        $configOfSorters = new SortersConfig();
        $configOfSorters->addField('id');

        $this->filterNames->expects(self::once())
            ->method('getSortFilterName')
            ->willReturn('sort');

        $this->context->setClassName(User::class);
        $this->context->setConfig($config);
        $this->context->setConfigOfSorters($configOfSorters);
        $this->processor->process($this->context);

        $filters = $this->context->getFilters();
        self::assertCount(1, $filters);
        /** @var SortFilter $sortFilter */
        $sortFilter = $filters->get('sort');
        self::assertEquals('orderBy', $sortFilter->getDataType());
        self::assertEquals(['id' => 'ASC'], $sortFilter->getDefaultValue());
        self::assertEquals(
            "Result sorting. Comma-separated fields, e.g. 'field1,-field2'. Allowed fields: id.",
            $sortFilter->getDescription()
        );
        self::assertFalse($filters->isIncludeInDefaultGroup('sort'));
    }

    public function testProcessForEntityWithIdentifierNotNamedId(): void
    {
        $config = new EntityDefinitionConfig();
        $config->setIdentifierFieldNames(['name']);
        $config->addField('name');

        $configOfSorters = new SortersConfig();
        $configOfSorters->addField('name');

        $this->filterNames->expects(self::once())
            ->method('getSortFilterName')
            ->willReturn('sort');

        $this->context->setClassName(Category::class);
        $this->context->setConfig($config);
        $this->context->setConfigOfSorters($configOfSorters);
        $this->processor->process($this->context);

        $filters = $this->context->getFilters();
        self::assertCount(1, $filters);
        /** @var SortFilter $sortFilter */
        $sortFilter = $filters->get('sort');
        self::assertEquals('orderBy', $sortFilter->getDataType());
        self::assertEquals(['name' => 'ASC'], $sortFilter->getDefaultValue());
        self::assertEquals(
            "Result sorting. Comma-separated fields, e.g. 'field1,-field2'. Allowed fields: name.",
            $sortFilter->getDescription()
        );
        self::assertFalse($filters->isIncludeInDefaultGroup('sort'));
    }

    public function testProcessForEntityWithSingleIdentifierAndSorterIsDisabled(): void
    {
        $config = new EntityDefinitionConfig();
        $config->setIdentifierFieldNames(['id']);
        $config->addField('id');

        $configOfSorters = new SortersConfig();
        $configOfSorters->addField('id')->setExcluded(true);

        $this->filterNames->expects(self::once())
            ->method('getSortFilterName')
            ->willReturn('sort');

        $this->context->setClassName(User::class);
        $this->context->setConfig($config);
        $this->context->setConfigOfSorters($configOfSorters);
        $this->processor->process($this->context);

        $filters = $this->context->getFilters();
        self::assertCount(1, $filters);
        /** @var SortFilter $sortFilter */
        $sortFilter = $filters->get('sort');
        self::assertEquals('orderBy', $sortFilter->getDataType());
        self::assertSame([], $sortFilter->getDefaultValue());
        self::assertEquals(
            "Result sorting. Comma-separated fields, e.g. 'field1,-field2'.",
            $sortFilter->getDescription()
        );
        self::assertFalse($filters->isIncludeInDefaultGroup('sort'));
    }

    public function testProcessForEntityWithCompositeIdentifier(): void
    {
        $config = new EntityDefinitionConfig();
        $config->setIdentifierFieldNames(['id', 'title']);
        $config->addField('id');
        $config->addField('title');

        $configOfSorters = new SortersConfig();
        $configOfSorters->addField('id');
        $configOfSorters->addField('title');

        $this->filterNames->expects(self::once())
            ->method('getSortFilterName')
            ->willReturn('sort');

        $this->context->setClassName(CompositeKeyEntity::class);
        $this->context->setConfig($config);
        $this->context->setConfigOfSorters($configOfSorters);
        $this->processor->process($this->context);

        $filters = $this->context->getFilters();
        self::assertCount(1, $filters);
        /** @var SortFilter $sortFilter */
        $sortFilter = $filters->get('sort');
        self::assertEquals('orderBy', $sortFilter->getDataType());
        self::assertEquals(['id' => 'ASC', 'title' => 'ASC'], $sortFilter->getDefaultValue());
        self::assertEquals(
            "Result sorting. Comma-separated fields, e.g. 'field1,-field2'. Allowed fields: id, title.",
            $sortFilter->getDescription()
        );
        self::assertFalse($filters->isIncludeInDefaultGroup('sort'));
    }

    public function testProcessForEntityWithCompositeIdentifierAndSorterForSomeIdentifierFieldsIsDisabled(): void
    {
        $config = new EntityDefinitionConfig();
        $config->setIdentifierFieldNames(['id', 'title']);
        $config->addField('id');
        $config->addField('title');

        $configOfSorters = new SortersConfig();
        $configOfSorters->addField('id')->setExcluded(true);
        $configOfSorters->addField('title');

        $this->filterNames->expects(self::once())
            ->method('getSortFilterName')
            ->willReturn('sort');

        $this->context->setClassName(CompositeKeyEntity::class);
        $this->context->setConfig($config);
        $this->context->setConfigOfSorters($configOfSorters);
        $this->processor->process($this->context);

        $filters = $this->context->getFilters();
        self::assertCount(1, $filters);
        /** @var SortFilter $sortFilter */
        $sortFilter = $filters->get('sort');
        self::assertEquals('orderBy', $sortFilter->getDataType());
        self::assertEquals(['title' => 'ASC'], $sortFilter->getDefaultValue());
        self::assertEquals(
            "Result sorting. Comma-separated fields, e.g. 'field1,-field2'. Allowed fields: title.",
            $sortFilter->getDescription()
        );
        self::assertFalse($filters->isIncludeInDefaultGroup('sort'));
    }

    public function testProcessWhenNoIdentifierFieldInConfig(): void
    {
        $config = new EntityDefinitionConfig();
        $config->setIdentifierFieldNames(['id']);

        $configOfSorters = new SortersConfig();
        $configOfSorters->addField('id');

        $this->filterNames->expects(self::once())
            ->method('getSortFilterName')
            ->willReturn('sort');

        $this->context->setClassName(User::class);
        $this->context->setConfig($config);
        $this->context->setConfigOfSorters($configOfSorters);
        $this->processor->process($this->context);

        $filters = $this->context->getFilters();
        self::assertCount(1, $filters);
        /** @var SortFilter $sortFilter */
        $sortFilter = $filters->get('sort');
        self::assertEquals('orderBy', $sortFilter->getDataType());
        self::assertEquals(['id' => 'ASC'], $sortFilter->getDefaultValue());
        self::assertEquals(
            "Result sorting. Comma-separated fields, e.g. 'field1,-field2'. Allowed fields: id.",
            $sortFilter->getDescription()
        );
        self::assertFalse($filters->isIncludeInDefaultGroup('sort'));
    }

    public function testProcessWhenConfigHasOrderByOption(): void
    {
        $config = new EntityDefinitionConfig();
        $config->setIdentifierFieldNames(['name']);
        $config->setOrderBy(['name' => 'DESC']);

        $configOfSorters = new SortersConfig();
        $configOfSorters->addField('name');

        $this->filterNames->expects(self::once())
            ->method('getSortFilterName')
            ->willReturn('sort');

        $this->context->setClassName(User::class);
        $this->context->setConfig($config);
        $this->context->setConfigOfSorters($configOfSorters);
        $this->processor->process($this->context);

        $filters = $this->context->getFilters();
        self::assertCount(1, $filters);
        /** @var SortFilter $sortFilter */
        $sortFilter = $filters->get('sort');
        self::assertEquals('orderBy', $sortFilter->getDataType());
        self::assertEquals(['name' => 'DESC'], $sortFilter->getDefaultValue());
        self::assertEquals(
            "Result sorting. Comma-separated fields, e.g. 'field1,-field2'. Allowed fields: name.",
            $sortFilter->getDescription()
        );
        self::assertFalse($filters->isIncludeInDefaultGroup('sort'));
    }

    public function testProcessForEntityWithoutIdentifier(): void
    {
        $config = new EntityDefinitionConfig();
        $configOfSorters = new SortersConfig();

        $this->filterNames->expects(self::once())
            ->method('getSortFilterName')
            ->willReturn('sort');

        $this->context->setClassName(User::class);
        $this->context->setConfig($config);
        $this->context->setConfigOfSorters($configOfSorters);
        $this->processor->process($this->context);

        $filters = $this->context->getFilters();
        self::assertCount(1, $filters);
        /** @var SortFilter $sortFilter */
        $sortFilter = $filters->get('sort');
        self::assertEquals('orderBy', $sortFilter->getDataType());
        self::assertEquals([], $sortFilter->getDefaultValue());
        self::assertEquals(
            "Result sorting. Comma-separated fields, e.g. 'field1,-field2'.",
            $sortFilter->getDescription()
        );
        self::assertFalse($filters->isIncludeInDefaultGroup('sort'));
    }

    public function testProcessForEntityWithRenamedIdentifierFieldAndSorterForThisFieldIsDisabled(): void
    {
        $config = new EntityDefinitionConfig();
        $config->setIdentifierFieldNames(['renamedId']);
        $config->addField('renamedId')->setPropertyPath('id');

        $configOfSorters = new SortersConfig();
        $sorterConfig = $configOfSorters->addField('renamedId');
        $sorterConfig->setPropertyPath('id');
        $sorterConfig->setExcluded(true);

        $this->filterNames->expects(self::once())
            ->method('getSortFilterName')
            ->willReturn('sort');

        $this->context->setClassName(User::class);
        $this->context->setConfig($config);
        $this->context->setConfigOfSorters($configOfSorters);
        $this->processor->process($this->context);

        $filters = $this->context->getFilters();
        self::assertCount(1, $filters);
        /** @var SortFilter $sortFilter */
        $sortFilter = $filters->get('sort');
        self::assertEquals('orderBy', $sortFilter->getDataType());
        self::assertSame([], $sortFilter->getDefaultValue());
        self::assertEquals(
            "Result sorting. Comma-separated fields, e.g. 'field1,-field2'.",
            $sortFilter->getDescription()
        );
        self::assertFalse($filters->isIncludeInDefaultGroup('sort'));
    }

    public function testProcessWhenSortFilterIsAlreadyAdded(): void
    {
        $sortFilter = new SortFilter(DataType::ORDER_BY);

        $this->filterNames->expects(self::once())
            ->method('getSortFilterName')
            ->willReturn('sort');

        $this->context->setClassName(Category::class);
        $this->context->setConfig(new EntityDefinitionConfig());
        $this->context->getFilters()->add('sort', $sortFilter, false);
        $this->processor->process($this->context);

        self::assertSame($sortFilter, $this->context->getFilters()->get('sort'));
        self::assertFalse($this->context->getFilters()->isIncludeInDefaultGroup('sort'));
    }

    public function testProcessWhenSortingIsDisabled(): void
    {
        $config = new EntityDefinitionConfig();
        $config->disableSorting();

        $this->filterNames->expects(self::once())
            ->method('getSortFilterName')
            ->willReturn('sort');

        $this->context->setConfig($config);
        $this->processor->process($this->context);

        self::assertCount(0, $this->context->getFilters());
    }
}
