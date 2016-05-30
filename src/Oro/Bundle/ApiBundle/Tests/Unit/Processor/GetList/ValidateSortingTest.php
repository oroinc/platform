<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList;

use Oro\Bundle\ApiBundle\Config\SorterFieldConfig;
use Oro\Bundle\ApiBundle\Config\SortersConfig;
use Oro\Bundle\ApiBundle\Filter\FilterCollection;
use Oro\Bundle\ApiBundle\Filter\SortFilter;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\GetList\ValidateSorting;
use Oro\Bundle\ApiBundle\Request\DataType;
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

    public function testProcessWhenQueryIsAlreadyBuilt()
    {
        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $this->context->setQuery($qb);
        $this->processor->process($this->context);

        $this->assertSame($qb, $this->context->getQuery());
    }

    public function testProcessWhenSortByExcludedFieldRequested()
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

        $this->assertEquals(
            [
                Error::createValidationError('sort constraint', 'Sorting by "id" field is not supported.')
                    ->setSource(ErrorSource::createByParameter('sort'))
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWhenNoSorters()
    {
        $sortersConfig = new SortersConfig();

        $this->prepareConfigs();

        $this->context->setConfigOfSorters($sortersConfig);
        $this->processor->process($this->context);

        $this->assertEquals(
            [
                Error::createValidationError('sort constraint', 'Sorting by "id" field is not supported.')
                    ->setSource(ErrorSource::createByParameter('sort'))
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWhenSortByNotAllowedFieldRequested()
    {
        $sortersConfig = new SortersConfig();
        $sorterConfig  = new SorterFieldConfig();
        $sorterConfig->setExcluded(true);
        $sortersConfig->addField('name', $sorterConfig);

        $this->prepareConfigs();

        $this->context->setConfigOfSorters($sortersConfig);
        $this->processor->process($this->context);

        $this->assertEquals(
            [
                Error::createValidationError('sort constraint', 'Sorting by "id" field is not supported.')
                    ->setSource(ErrorSource::createByParameter('sort'))
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWhenSortBySeveralNotAllowedFieldRequested()
    {
        $sortersConfig = new SortersConfig();
        $sorterConfig  = new SorterFieldConfig();
        $sorterConfig->setExcluded(true);
        $sortersConfig->addField('name', $sorterConfig);

        $this->prepareConfigs('id,-label');

        $this->context->setConfigOfSorters($sortersConfig);
        $this->processor->process($this->context);

        $this->assertEquals(
            [
                Error::createValidationError('sort constraint', 'Sorting by "id, label" fields are not supported.')
                    ->setSource(ErrorSource::createByParameter('sort'))
            ],
            $this->context->getErrors()
        );
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

    /**
     * @param string $sortBy
     */
    protected function prepareConfigs($sortBy = '-id')
    {
        $sorterFilter = new SortFilter(DataType::ORDER_BY);
        $filters      = new FilterCollection();
        $filters->add('sort', $sorterFilter);

        $request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();
        $request->expects($this->once())
            ->method('getQueryString')
            ->willReturn('sort=' . $sortBy);
        $filterValues = new RestFilterValueAccessor($request);

        // emulate sort normalizer
        $orderBy = [];
        $items = explode(',', $sortBy);
        foreach ($items as $item) {
            $item = trim($item);
            if (0 === strpos($item, '-')) {
                $orderBy[substr($item, 1)] = 'DESC';
            } else {
                $orderBy[$item] = 'ASC';
            }
        }
        $filterValues->get('sort')->setValue($orderBy);

        $this->context->set('filters', $filters);
        $this->context->setFilterValues($filterValues);
    }
}
