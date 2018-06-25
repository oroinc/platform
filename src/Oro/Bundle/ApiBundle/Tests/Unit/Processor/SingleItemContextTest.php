<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor;

use Oro\Bundle\ApiBundle\Processor\SingleItemContext;

class SingleItemContextTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $configProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $metadataProvider;

    /** @var SingleItemContext */
    protected $context;

    protected function setUp()
    {
        $this->configProvider   = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->metadataProvider = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\MetadataProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->context = new SingleItemContext($this->configProvider, $this->metadataProvider);
    }

    public function testId()
    {
        $this->assertNull($this->context->getId());

        $this->context->setId('test');
        $this->assertEquals('test', $this->context->getId());
        $this->assertEquals('test', $this->context->get(SingleItemContext::ID));
    }
}
