<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor;

use Oro\Bundle\ApiBundle\Processor\SingleItemContext;

class SingleItemContextTest extends \PHPUnit_Framework_TestCase
{
    public function testVersion()
    {
        $configProvider = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $context = new SingleItemContext($configProvider);

        $this->assertNull($context->getId());

        $context->setId('test');
        $this->assertEquals('test', $context->getId());
        $this->assertEquals('test', $context->get(SingleItemContext::ID));
    }
}
