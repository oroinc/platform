<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList;

use Oro\Bundle\ApiBundle\Filter\ComparisonFilter;
use Oro\Bundle\ApiBundle\Processor\Get\GetContext;
use Oro\Bundle\ApiBundle\Processor\GetList\NormalizeFilterValues;
use Oro\Bundle\ApiBundle\Request\RestFilterValueAccessor;

class NormalizeFilterValuesTest extends \PHPUnit_Framework_TestCase
{
    /** @var GetContext */
    protected $context;

    /** @var NormalizeFilterValues */
    protected $processor;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $valueNormalizer;

    protected function setUp()
    {
        $configProvider   = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $metadataProvider = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\MetadataProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->context = new GetContext($configProvider, $metadataProvider);
        $this->context->setRequestType('REST');
        $this->valueNormalizer = $this->getMockBuilder('Oro\Bundle\ApiBundle\Request\ValueNormalizer')
            ->disableOriginalConstructor()
            ->getMock();
        $this->processor = new NormalizeFilterValues($this->valueNormalizer);
    }

    public function testProcessOnExistingQuery()
    {
        $this->context->setQuery(new \stdClass());
        $context = clone $this->context;
        $this->processor->process($this->context);
        $this->assertEquals($context, $this->context);
    }

    public function testProcess()
    {
        $filters = $this->context->getFilters();
        $integerFilter = new ComparisonFilter('integer');
        $stringFilter = new ComparisonFilter('string');
        $filters->add('id', $integerFilter);
        $filters->add('label', $stringFilter);

        $request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();
        $request->expects($this->once())
            ->method('getQueryString')
            ->willReturn('id=1&label=test');
        $filterValues = new RestFilterValueAccessor($request);
        $this->context->setFilterValues($filterValues);

        $this->valueNormalizer->expects($this->exactly(2))
            ->method('normalizeValue')
            ->willReturnMap(
                [
                    ['1', 'integer', ['REST'], false, 1],
                    ['test', 'string', ['REST'], false, 'test'],
                ]
            );

        $this->processor->process($this->context);
        $this->assertTrue(is_int($filterValues->get('id')->getValue()));
        $this->assertEquals(1, $filterValues->get('id')->getValue());
        $this->assertTrue(is_string($filterValues->get('label')->getValue()));
        $this->assertEquals('test', $filterValues->get('label')->getValue());
    }
}
