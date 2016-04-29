<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model\ActionGroup;

use Symfony\Component\PropertyAccess\PropertyPath;

use Oro\Bundle\ActionBundle\Model\ActionGroup\PropertyMapper;
use Oro\Bundle\ActionBundle\Model\ActionGroupExecutionArgs;

use Oro\Component\Action\Model\ContextAccessor;

class PropertyMapperTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|ContextAccessor */
    protected $mockContextAccessor;

    /** @var PropertyMapper */
    protected $propertyMapper;

    protected function setUp()
    {
        $this->mockContextAccessor = $this->getMockBuilder('Oro\Component\Action\Model\ContextAccessor')->getMock();
        $this->propertyMapper = new PropertyMapper($this->mockContextAccessor);
    }

    protected function tearDown()
    {
        unset($this->mockContextAccessor, $this->propertyMapper);
    }

    public function testAccessorUsage()
    {
        $this->assertAttributeSame($this->mockContextAccessor, 'accessor', $this->propertyMapper);
    }

    public function testAccessorEnsured()
    {
        $this->assertAttributeInstanceOf(
            'Oro\Component\Action\Model\ContextAccessor',
            'accessor',
            $this->propertyMapper
        );
    }

    public function testMapToArgs()
    {
        $pp1 = new PropertyPath('contextParam1');
        $pp2 = new PropertyPath('contextParam2');

        $this->mockContextAccessor
            ->expects($this->at(0))
            ->method('getValue')
            ->with([], $pp1)
            ->willReturn('val1');
        $this->mockContextAccessor
            ->expects($this->at(1))
            ->method('getValue')
            ->with([], $pp2)
            ->willReturn('val2');

        /** @var \PHPUnit_Framework_MockObject_MockObject|ActionGroupExecutionArgs $mockExecutionArgs */
        $mockExecutionArgs = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\ActionGroupExecutionArgs')
            ->disableOriginalConstructor()->getMock();

        $mockExecutionArgs->expects($this->at(0))->method('addParameter')->with('arg1', 'val1');
        $mockExecutionArgs->expects($this->at(1))->method('addParameter')->with('arg2', ['embedded' => 'val2']);
        $mockExecutionArgs->expects($this->at(2))->method('addParameter')->with('arg3', 'simple value');

        $this->propertyMapper->toArgs(
            $mockExecutionArgs,
            [
                'arg1' => new PropertyPath('contextParam1'),
                'arg2' => [
                    'embedded' => new PropertyPath('contextParam2')
                ],
                'arg3' => 'simple value',
            ],
            []
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Parameters map must be array or implements \Traversable interface
     */
    public function testNonTraversableAssertionException()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|ActionGroupExecutionArgs $mockExecutionArgs */
        $mockExecutionArgs = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\ActionGroupExecutionArgs')
            ->disableOriginalConstructor()->getMock();

        $this->propertyMapper->toArgs($mockExecutionArgs, (object)[], []);
    }

    public function testTransfer()
    {
        $from = (object)['k' => 'v'];
        $to = (object)['t' => null];

        $sourcePropertyPath = new PropertyPath('source');

        $this->mockContextAccessor->expects($this->once())
            ->method('getValue')
            ->with($from, $sourcePropertyPath)
            ->willReturn('data');

        $this->mockContextAccessor->expects($this->once())
            ->method('setValue')
            ->with($to, 'target', 'data');

        $this->propertyMapper->transfer($from, ['target' => $sourcePropertyPath], $to);
    }
}
