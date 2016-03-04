<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList;

use Oro\Bundle\ApiBundle\Config\SorterFieldConfig;
use Oro\Bundle\ApiBundle\Config\SortersConfig;
use Oro\Bundle\ApiBundle\Filter\FilterCollection;
use Oro\Bundle\ApiBundle\Filter\SortFilter;
use Oro\Bundle\ApiBundle\Processor\GetList\ValidateSorting;
use Oro\Bundle\ApiBundle\Request\RestFilterValueAccessor;

class ValidateSortingTest extends GetListProcessorTestCase
{
    /** @var ValidateSorting */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processor = new ValidateSorting();
    }

    public function testProcessOnExistingQuery()
    {
        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $this->context->setQuery($qb);
        $this->processor->process($this->context);

        $this->assertSame($qb, $this->context->getQuery());
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException
     * @expectedExceptionMessage Sorting by "id" is not supported.
     */
    public function testProcessOnExcludedField()
    {
        $sortersConfig = new SortersConfig();
        $sorterConfig  = new SorterFieldConfig();
        $sorterConfig->setExcluded(true);
        $sortersConfig->addField('id', $sorterConfig);

        $sorterFilter = new SortFilter('integer');
        $filters      = new FilterCollection();
        $filters->add('sort', $sorterFilter);

        $this->prepareConfigs();

        $this->context->setConfigOfSorters($sortersConfig);
        $this->context->set('filters', $filters);
        $this->processor->process($this->context);
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException
     * @expectedExceptionMessage Sorting by "id" is not supported.
     */
    public function testProcessOnEmptySortersConfig()
    {
        $sortersConfig = new SortersConfig();

        $this->prepareConfigs();

        $this->context->setConfigOfSorters($sortersConfig);
        $this->processor->process($this->context);
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException
     * @expectedExceptionMessage Sorting by "id" is not supported.
     */
    public function testProcessOnNonConfiguredSorterField()
    {
        $sortersConfig = new SortersConfig();
        $sorterConfig  = new SorterFieldConfig();
        $sorterConfig->setExcluded(true);
        $sortersConfig->addField('name', $sorterConfig);

        $this->prepareConfigs();

        $this->context->setConfigOfSorters($sortersConfig);
        $this->processor->process($this->context);
    }

    public function testProcess()
    {
        $sortersConfig = new SortersConfig();
        $sorterConfig  = new SorterFieldConfig();
        $sorterConfig->setExcluded(false);
        $sortersConfig->addField('id', $sorterConfig);

        $this->prepareConfigs();

        $this->context->setConfigOfSorters($sortersConfig);
        $this->processor->process($this->context);
    }


    protected function prepareConfigs()
    {
        $sorterFilter = new SortFilter('integer');
        $filters      = new FilterCollection();
        $filters->add('sort', $sorterFilter);

        $request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();
        $request->expects($this->once())
            ->method('getQueryString')
            ->willReturn('sort=-id');
        $filterValues = new RestFilterValueAccessor($request);
        // emulate sort normalizer
        $filterValues->get('sort')->setValue(['id' => 'DESC']);

        $this->context->set('filters', $filters);
        $this->context->setFilterValues($filterValues);
    }
}
