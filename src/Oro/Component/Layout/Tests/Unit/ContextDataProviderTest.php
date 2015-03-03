<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\ContextAwareDataProvider;

class ContextAwareDataProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testGetIdentifier()
    {
        $key          = 'foo';
        $context      = $this->getMock('Oro\Component\Layout\ContextInterface');
        $dataProvider = new ContextAwareDataProvider($context, $key);
        $this->assertEquals('context.' . $key, $dataProvider->getIdentifier());
    }

    public function testGetData()
    {
        $key          = 'foo';
        $data         = new \stdClass();
        $context      = $this->getMock('Oro\Component\Layout\ContextInterface');
        $dataProvider = new ContextAwareDataProvider($context, $key);

        $context->expects($this->once())
            ->method('offsetGet')
            ->with($key)
            ->will($this->returnValue($data));

        $this->assertSame($data, $dataProvider->getData());
    }
}
