<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Model\Strategy;

use Oro\Bundle\EntityMergeBundle\Model\Strategy\DelegateStrategy;

class DelegateStrategyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DelegateStrategy $strategy
     */
    protected $strategy;

    protected function setUp()
    {
        $this->strategy = new DelegateStrategy();
    }

    public function testConstructor()
    {
        $foo = $this->createStrategy();
        $bar = $this->createStrategy();

        $strategy = new DelegateStrategy(array($foo, $bar));

        $this->assertAttributeEquals(array($foo, $bar), 'elements', $strategy);
    }

    public function testAdd()
    {
        $this->strategy->add($foo = $this->createStrategy());
        $this->strategy->add($bar = $this->createStrategy());

        $this->assertAttributeEquals(array($foo, $bar), 'elements', $this->strategy);
    }

    public function testSupportsTrueLast()
    {
        $this->strategy->add($foo = $this->createStrategy());
        $this->strategy->add($bar = $this->createStrategy());
        $this->strategy->add($baz = $this->createStrategy());

        $data = $this->createFieldData();

        $foo->expects($this->once())
            ->method('supports')
            ->with($data)
            ->will($this->returnValue(false));

        $bar->expects($this->once())
            ->method('supports')
            ->with($data)
            ->will($this->returnValue(false));

        $baz->expects($this->once())
            ->method('supports')
            ->with($data)
            ->will($this->returnValue(true));

        $this->assertTrue($this->strategy->supports($data));
    }

    public function testSupportsTrueFirst()
    {
        $this->strategy->add($foo = $this->createStrategy());
        $this->strategy->add($bar = $this->createStrategy());

        $data = $this->createFieldData();

        $foo->expects($this->once())
            ->method('supports')
            ->with($data)
            ->will($this->returnValue(true));

        $bar->expects($this->never())->method('supports');

        $this->assertTrue($this->strategy->supports($data));
    }

    public function testSupportsFalse()
    {
        $this->strategy->add($foo = $this->createStrategy());
        $this->strategy->add($bar = $this->createStrategy());

        $data = $this->createFieldData();

        $foo->expects($this->once())
            ->method('supports')
            ->with($data)
            ->will($this->returnValue(false));

        $bar->expects($this->once())
            ->method('supports')
            ->with($data)
            ->will($this->returnValue(false));

        $this->assertFalse($this->strategy->supports($data));
    }

    public function testMerge()
    {
        $this->strategy->add($foo = $this->createStrategy());

        $data = $this->createFieldData();

        $foo->expects($this->once())
            ->method('supports')
            ->with($data)
            ->will($this->returnValue(true));

        $foo->expects($this->once())
            ->method('merge')
            ->with($data);

        $this->strategy->merge($data);
    }

    /**
     * @expectedException \Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException
     * @expectedExceptionMessage Cannot find merge strategy for "test" field.
     */
    public function testMergeFails()
    {
        $this->strategy->add($foo = $this->createStrategy());

        $data = $this->createFieldData();

        $data->expects($this->once())
            ->method('getFieldName')
            ->will($this->returnValue('test'));

        $foo->expects($this->once())
            ->method('supports')
            ->with($data)
            ->will($this->returnValue(false));

        $this->strategy->merge($data);
    }

    protected function createStrategy()
    {
        return $this->getMock('Oro\Bundle\EntityMergeBundle\Model\Strategy\StrategyInterface');
    }

    protected function createFieldData()
    {
        return $this->getMockBuilder('Oro\Bundle\EntityMergeBundle\Data\FieldData')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
