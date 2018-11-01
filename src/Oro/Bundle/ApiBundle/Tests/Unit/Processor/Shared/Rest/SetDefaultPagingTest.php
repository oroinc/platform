<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared\Rest;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Filter\PageNumberFilter;
use Oro\Bundle\ApiBundle\Filter\PageSizeFilter;
use Oro\Bundle\ApiBundle\Processor\Shared\Rest\SetDefaultPaging;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorTestCase;

class SetDefaultPagingTest extends GetListProcessorTestCase
{
    /** @var SetDefaultPaging */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processor = new SetDefaultPaging();
    }

    public function testProcessWhenQueryIsAlreadyExist()
    {
        $qb = $this->createMock(QueryBuilder::class);

        $this->context->setQuery($qb);
        $this->processor->process($this->context);

        self::assertSame($qb, $this->context->getQuery());
    }

    public function testProcessWithDefaultPaging()
    {
        $this->context->setConfig(new EntityDefinitionConfig());
        $this->processor->process($this->context);

        $filters = $this->context->getFilters();
        self::assertCount(2, $filters);
        /** @var PageSizeFilter $pageSizeFilter */
        $pageSizeFilter = $filters->get('limit');
        self::assertEquals(10, $pageSizeFilter->getDefaultValue());
        /** @var PageNumberFilter $pageNumberFilter */
        $pageNumberFilter = $filters->get('page');
        self::assertEquals(1, $pageNumberFilter->getDefaultValue());

        // check that filters are added in correct order
        self::assertEquals(['limit', 'page'], array_keys($filters->all()));
    }

    public function testProcessWhenPageSizeExistsInConfig()
    {
        $config = new EntityDefinitionConfig();
        $config->setPageSize(123);

        $this->context->setConfig($config);
        $this->processor->process($this->context);

        $filters = $this->context->getFilters();
        self::assertCount(2, $filters);
        /** @var PageSizeFilter $pageSizeFilter */
        $pageSizeFilter = $filters->get('limit');
        self::assertEquals(123, $pageSizeFilter->getDefaultValue());
        /** @var PageNumberFilter $pageNumberFilter */
        $pageNumberFilter = $filters->get('page');
        self::assertEquals(1, $pageNumberFilter->getDefaultValue());

        // check that filters are added in correct order
        self::assertEquals(['limit', 'page'], array_keys($filters->all()));
    }

    public function testProcessWhenPagingIsDisabled()
    {
        $config = new EntityDefinitionConfig();
        $config->setPageSize(-1);

        $this->context->setConfig($config);
        $this->processor->process($this->context);

        $filters = $this->context->getFilters();
        self::assertCount(0, $filters);
    }
}
