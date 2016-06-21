<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared\Rest;

use Oro\Bundle\ApiBundle\Filter\SortFilter;
use Oro\Bundle\ApiBundle\Processor\Shared\Rest\SetDefaultSorting;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorOrmRelatedTestCase;

class SetDefaultSortingTest extends GetListProcessorOrmRelatedTestCase
{
    /** @var SetDefaultSorting */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processor = new SetDefaultSorting($this->doctrineHelper);
    }

    public function testProcessOnExistingQuery()
    {
        $qb = $this->getQueryBuilderMock();

        $this->context->setQuery($qb);
        $this->processor->process($this->context);

        $this->assertSame($qb, $this->context->getQuery());
    }

    public function testProcessForEntityWithIdentifierNamedId()
    {
        $this->context->setClassName('Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User');
        $this->processor->process($this->context);

        $filters = $this->context->getFilters();
        $this->assertEquals(1, $filters->count());
        /** @var SortFilter $sortFilter */
        $sortFilter = $filters->get('sort');
        $this->assertEquals('orderBy', $sortFilter->getDataType());
        $this->assertEquals(['id' => 'ASC'], $sortFilter->getDefaultValue());
    }

    public function testProcessForEntityWithIdentifierNotNamedId()
    {
        $this->context->setClassName('Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Category');
        $this->processor->process($this->context);

        $filters = $this->context->getFilters();
        $this->assertEquals(1, $filters->count());
        /** @var SortFilter $sortFilter */
        $sortFilter = $filters->get('sort');
        $this->assertEquals('orderBy', $sortFilter->getDataType());
        $this->assertEquals(['name' => 'ASC'], $sortFilter->getDefaultValue());
    }

    public function testProcessForEntityWithCompositeIdentifier()
    {
        $this->context->setClassName('Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\CompositeKeyEntity');
        $this->processor->process($this->context);

        $filters = $this->context->getFilters();
        $this->assertEquals(1, $filters->count());
        /** @var SortFilter $sortFilter */
        $sortFilter = $filters->get('sort');
        $this->assertEquals('orderBy', $sortFilter->getDataType());
        $this->assertEquals(['id' => 'ASC', 'title' => 'ASC'], $sortFilter->getDefaultValue());
    }
}
