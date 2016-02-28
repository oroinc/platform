<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList;

use Oro\Bundle\ApiBundle\Config\SorterFieldConfig;
use Oro\Bundle\ApiBundle\Config\SortersConfig;
use Oro\Bundle\ApiBundle\Filter\FilterCollection;
use Oro\Bundle\ApiBundle\Filter\SortFilter;
use Oro\Bundle\ApiBundle\Processor\GetList\GetListContext;
use Oro\Bundle\ApiBundle\Processor\GetList\ValidateSorting;
use Oro\Bundle\ApiBundle\Request\RestFilterValueAccessor;

class ValidateSortingTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $metadataProvider;

    /** @var GetListContext */
    protected $context;

    /** @var ValidateSorting */
    protected $processor;

    protected function setUp()
    {
        $this->configProvider   = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->metadataProvider = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\MetadataProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->context = new GetListContext($this->configProvider, $this->metadataProvider);
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
        $sorterConfig = new SorterFieldConfig();
        $sorterConfig->setExcluded(true);
        $sortersConfig->addField('id', $sorterConfig);
        $this->context->setConfigOfSorters($sortersConfig);

        $sorterFilter = new SortFilter('integer');
        $filters = new FilterCollection();
        $filters->add('sort', $sorterFilter);
        $this->context->set('filters', $filters);

        $this->prepareConfigs();

        $this->processor->process($this->context);
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException
     * @expectedExceptionMessage Sorting by "id" is not supported.
     */
    public function testProcessOnEmptySortersConfig()
    {
        $sortersConfig = new SortersConfig();
        $this->context->setConfigOfSorters($sortersConfig);

        $this->prepareConfigs();

        $this->processor->process($this->context);
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException
     * @expectedExceptionMessage Sorting by "id" is not supported.
     */
    public function testProcessOnNonConfiguredSorterField()
    {
        $sortersConfig = new SortersConfig();
        $sorterConfig = new SorterFieldConfig();
        $sorterConfig->setExcluded(true);
        $sortersConfig->addField('name', $sorterConfig);
        $this->context->setConfigOfSorters($sortersConfig);

        $this->prepareConfigs();

        $this->processor->process($this->context);
    }

    public function testProcess()
    {
        $sortersConfig = new SortersConfig();
        $sorterConfig = new SorterFieldConfig();
        $sorterConfig->setExcluded(false);
        $sortersConfig->addField('id', $sorterConfig);
        $this->context->setConfigOfSorters($sortersConfig);

        $this->prepareConfigs();

        $this->processor->process($this->context);
    }


    protected function prepareConfigs()
    {
        $sorterFilter = new SortFilter('integer');
        $filters = new FilterCollection();
        $filters->add('sort', $sorterFilter);
        $this->context->set('filters', $filters);

        $request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();
        $request->expects($this->once())
            ->method('getQueryString')
            ->willReturn('sort=-id');
        $filterValues = new RestFilterValueAccessor($request);
        // emulate sort normalizer
        $filterValues->get('sort')->setValue(['id' => 'DESC']);
        $this->context->setFilterValues($filterValues);
    }
}
