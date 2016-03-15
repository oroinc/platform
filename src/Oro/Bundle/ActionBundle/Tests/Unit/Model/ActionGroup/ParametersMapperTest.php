<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model\ActionGroup;

use Oro\Bundle\ActionBundle\Model\ActionGroup\ParametersMapper;

class ParametersMapperTest extends \PHPUnit_Framework_TestCase
{
    public function testAccessorUsage()
    {
        $mockAccessor = $this->getMockBuilder('\Oro\Component\Action\Model\ContextAccessor')->getMock();
        $instance = new ParametersMapper($mockAccessor);

        $this->assertAttributeSame($mockAccessor, 'accessor', $instance);
    }

    public function testAccessorEnsured()
    {
        $instance = new ParametersMapper();

        $this->assertAttributeInstanceOf('\Oro\Component\Action\Model\ContextAccessor', 'accessor', $instance);
    }
}
