<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList;

use Oro\Bundle\ApiBundle\Processor\ListContext;

class ListContextTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $metadataProvider;

    /** @var ListContext */
    protected $context;

    protected function setUp()
    {
        $this->configProvider   = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->metadataProvider = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\MetadataProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->context = new ListContext($this->configProvider, $this->metadataProvider);
    }

    public function testTotalCountCallback()
    {
        $this->assertNull($this->context->getTotalCountCallback());

        $totalCountCallback = [$this, 'calculateTotalCount'];

        $this->context->setTotalCountCallback($totalCountCallback);
        $this->assertEquals($totalCountCallback, $this->context->getTotalCountCallback());
        $this->assertEquals($totalCountCallback, $this->context->get(ListContext::TOTAL_COUNT_CALLBACK));
    }
}
