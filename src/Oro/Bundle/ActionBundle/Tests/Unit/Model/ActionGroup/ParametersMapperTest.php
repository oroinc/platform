<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model\ActionGroup;

use Symfony\Component\PropertyAccess\PropertyPath;

use Oro\Bundle\ActionBundle\Model\ActionGroup\ParametersMapper;

class ParametersMapperTest extends \PHPUnit_Framework_TestCase
{
    public function testAccessorUsage()
    {
        $mockAccessor = $this->getMockBuilder('Oro\Component\Action\Model\ContextAccessor')->getMock();
        $instance = new ParametersMapper($mockAccessor);

        $this->assertAttributeSame($mockAccessor, 'accessor', $instance);
    }

    public function testAccessorEnsured()
    {
        $instance = new ParametersMapper();

        $this->assertAttributeInstanceOf('Oro\Component\Action\Model\ContextAccessor', 'accessor', $instance);
    }

    public function testMapToArgs()
    {
        $mockContextAccessor = $this->getMockBuilder('Oro\Component\Action\Model\ContextAccessor')->getMock();

        $instance = new ParametersMapper($mockContextAccessor);

        $pp1 = new PropertyPath('contextParam1');
        $pp2 = new PropertyPath('contextParam2');

        $mockContextAccessor
            ->expects($this->at(0))
            ->method('getValue')
            ->with([], $pp1)
            ->willReturn('val1');
        $mockContextAccessor
            ->expects($this->at(1))
            ->method('getValue')
            ->with([], $pp2)
            ->willReturn('val2');

        $mockExecutionArgs = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\ActionGroupExecutionArgs')
            ->disableOriginalConstructor()->getMock();

        $mockExecutionArgs->expects($this->at(0))->method('addArgument')->with('arg1', 'val1');
        $mockExecutionArgs->expects($this->at(1))->method('addArgument')->with('arg2', ['embedded' => 'val2']);
        $mockExecutionArgs->expects($this->at(2))->method('addArgument')->with('arg3', 'simple value');

        $instance->mapToArgs(
            $mockExecutionArgs,
            [
                'arg1' => new PropertyPath('contextParam1'),
                'arg2' => [
                    'embedded' => new PropertyPath('contextParam2')
                ],
                'arg3' => 'simple value'
            ],
            []
        );
    }

    public function testNonTraversableAssertionException()
    {
        $instance = new ParametersMapper();

        $mockExecutionArgs = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\ActionGroupExecutionArgs')
            ->disableOriginalConstructor()->getMock();

        $this->setExpectedException(
            '\InvalidArgumentException',
            'Parameters map must be array or implements \Traversable interface'
        );

        $instance->mapToArgs($mockExecutionArgs, (object)[], []);
    }
}
