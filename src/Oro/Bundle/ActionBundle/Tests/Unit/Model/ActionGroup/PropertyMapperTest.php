<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model\ActionGroup;

use Symfony\Component\PropertyAccess\PropertyPath;

use Oro\Bundle\ActionBundle\Model\ActionGroup\PropertyMapper;

class PropertyMapperTest extends \PHPUnit_Framework_TestCase
{
    public function testAccessorUsage()
    {
        $mockAccessor = $this->getMockBuilder('Oro\Component\Action\Model\ContextAccessor')->getMock();
        $instance = new PropertyMapper($mockAccessor);

        $this->assertAttributeSame($mockAccessor, 'accessor', $instance);
    }

    public function testAccessorEnsured()
    {
        $instance = new PropertyMapper();

        $this->assertAttributeInstanceOf('Oro\Component\Action\Model\ContextAccessor', 'accessor', $instance);
    }

    public function testMapToArgs()
    {
        $mockContextAccessor = $this->getMockBuilder('Oro\Component\Action\Model\ContextAccessor')->getMock();

        $instance = new PropertyMapper($mockContextAccessor);

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

        $mockExecutionArgs->expects($this->at(0))->method('addParameter')->with('arg1', 'val1');
        $mockExecutionArgs->expects($this->at(1))->method('addParameter')->with('arg2', ['embedded' => 'val2']);
        $mockExecutionArgs->expects($this->at(2))->method('addParameter')->with('arg3', 'simple value');

        $instance->toArgs(
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
        $instance = new PropertyMapper();

        $mockExecutionArgs = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\ActionGroupExecutionArgs')
            ->disableOriginalConstructor()->getMock();

        $this->setExpectedException(
            '\InvalidArgumentException',
            'Parameters map must be array or implements \Traversable interface'
        );

        $instance->toArgs($mockExecutionArgs, (object)[], []);
    }


    public function testTransfer()
    {
        $mockContextAccessor = $this->getMockBuilder('Oro\Component\Action\Model\ContextAccessor')->getMock();

        $instance = new PropertyMapper($mockContextAccessor);

        $from = (object)['k' => 'v'];
        $to = (object)['t' => null];

        $sourcePropertyPath = new PropertyPath('source');

        $mockContextAccessor->expects($this->once())
            ->method('getValue')
            ->with($from, $sourcePropertyPath)
            ->willReturn('data');

        $mockContextAccessor->expects($this->once())
            ->method('setValue')
            ->with($to, 'target', 'data');

        $instance->transfer($from, ['target' => $sourcePropertyPath], $to);
    }
}
