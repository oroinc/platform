<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared\JsonApi;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Filter\SortFilter;
use Oro\Bundle\ApiBundle\Processor\Shared\JsonApi\SetDefaultSorting;
use Oro\Bundle\ApiBundle\Request\RequestType;
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

    public function testProcessWhenQueryIsAlreadyExist()
    {
        $qb = $this->getQueryBuilderMock();

        $this->context->setQuery($qb);
        $this->processor->process($this->context);

        $this->assertSame($qb, $this->context->getQuery());
    }

    public function testProcessForEntityWithIdentifierNamedId()
    {
        $this->context->getRequestType()->add(RequestType::JSON_API);
        $this->context->setClassName('Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User');
        $this->context->setConfig(new EntityDefinitionConfig());
        $this->processor->process($this->context);

        $filters = $this->context->getFilters();
        $this->assertCount(1, $filters);
        /** @var SortFilter $sortFilter */
        $sortFilter = $filters->get('sort');
        $this->assertEquals('orderBy', $sortFilter->getDataType());
        $this->assertEquals(['id' => 'ASC'], $sortFilter->getDefaultValue());
    }

    public function testProcessForEntityWithIdentifierNotNamedId()
    {
        $this->context->getRequestType()->add(RequestType::JSON_API);
        $this->context->setClassName('Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Category');
        $this->context->setConfig(new EntityDefinitionConfig());
        $this->processor->process($this->context);

        $filters = $this->context->getFilters();
        $this->assertCount(1, $filters);
        /** @var SortFilter $sortFilter */
        $sortFilter = $filters->get('sort');
        $this->assertEquals('orderBy', $sortFilter->getDataType());
        $this->assertEquals(['id' => 'ASC'], $sortFilter->getDefaultValue());
    }

    public function testProcessForEntityWithCompositeIdentifier()
    {
        $this->context->setClassName('Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\CompositeKeyEntity');
        $this->context->setConfig(new EntityDefinitionConfig());
        $this->processor->process($this->context);

        $filters = $this->context->getFilters();
        $this->assertCount(1, $filters);
        /** @var SortFilter $sortFilter */
        $sortFilter = $filters->get('sort');
        $this->assertEquals('orderBy', $sortFilter->getDataType());
        $this->assertEquals(['id' => 'ASC'], $sortFilter->getDefaultValue());
    }

    public function testProcessWhenSortingIsDisabled()
    {
        $config = new EntityDefinitionConfig();
        $config->disableSorting();

        $this->context->getRequestType()->add(RequestType::JSON_API);
        $this->context->setConfig($config);
        $this->processor->process($this->context);

        $filters = $this->context->getFilters();
        $this->assertCount(0, $filters);
    }
}
