<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList;

use Oro\Bundle\ApiBundle\Processor\GetList\GetListContext;

class GetListContextTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $metadataProvider;

    /** @var GetListContext */
    protected $context;

    protected function setUp()
    {
        $this->configProvider   = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->metadataProvider = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\MetadataProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->context = new GetListContext($this->configProvider, $this->metadataProvider);
    }

    public function testTotalCountCallback()
    {
        $this->assertNull($this->context->getTotalCountCallback());

        $totalCountCallback = [$this, 'calculateTotalCount'];

        $this->context->setTotalCountCallback($totalCountCallback);
        $this->assertEquals($totalCountCallback, $this->context->getTotalCountCallback());
        $this->assertEquals($totalCountCallback, $this->context->get(GetListContext::TOTAL_COUNT_CALLBACK));
    }
}
