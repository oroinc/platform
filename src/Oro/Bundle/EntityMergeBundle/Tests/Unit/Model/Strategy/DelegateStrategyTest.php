<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Model\Strategy;

use Oro\Bundle\EntityMergeBundle\Model\Strategy\DelegateStrategy;

class DelegateStrategyTest extends \PHPUnit\Framework\TestCase
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
        $foo = $this->createStrategy('foo');
        $bar = $this->createStrategy('bar');

        $strategy = new DelegateStrategy(array($foo, $bar));

        $expected = [
            'foo' => [
                DelegateStrategy::STRATEGY_KEY => $foo,
                DelegateStrategy::PRIORITY_KEY => DelegateStrategy::DEFAULT_PRIORITY
            ],
            'bar' => [
                DelegateStrategy::STRATEGY_KEY => $bar,
                DelegateStrategy::PRIORITY_KEY => DelegateStrategy::DEFAULT_PRIORITY
            ],
        ];

        $this->assertAttributeEquals($expected, 'elements', $strategy);
    }

    public function testAdd()
    {
        $this->strategy->add($foo = $this->createStrategy('foo'), 1);
        $this->strategy->add($bar = $this->createStrategy('bar'), 2);
        $this->strategy->add($defaultPriority = $this->createStrategy('defaultPriority'));

        $expected = [
            'bar' => [DelegateStrategy::STRATEGY_KEY => $bar, DelegateStrategy::PRIORITY_KEY => 2],
            'foo' => [DelegateStrategy::STRATEGY_KEY => $foo, DelegateStrategy::PRIORITY_KEY => 1],
            'defaultPriority' => [
                DelegateStrategy::STRATEGY_KEY => $defaultPriority,
                DelegateStrategy::PRIORITY_KEY => DelegateStrategy::DEFAULT_PRIORITY
            ],
        ];

        $this->assertAttributeEquals($expected, 'elements', $this->strategy);
    }

    public function testSupportsTrueLast()
    {
        $this->strategy->add($foo = $this->createStrategy('foo'));
        $this->strategy->add($bar = $this->createStrategy('bar'));
        $this->strategy->add($baz = $this->createStrategy('baz'));

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
        $this->strategy->add($foo = $this->createStrategy('foo'));
        $this->strategy->add($bar = $this->createStrategy('bar'));

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
        $this->strategy->add($foo = $this->createStrategy('foo'));
        $this->strategy->add($bar = $this->createStrategy('bar'));

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
        $this->strategy->add($lowPriorityApplicable = $this->createStrategy('lowPriorityApplicable'), -1);
        $this->strategy->add($defaultPriorityApplicable = $this->createStrategy('defaultPriorityApplicable'));
        $this->strategy->add($highPriorityNotApplicable = $this->createStrategy('highPriorityNotApplicable'), 100);

        $data = $this->createFieldData();

        $lowPriorityApplicable->expects($this->never())
            ->method('supports');

        $highPriorityNotApplicable->expects($this->once())
            ->method('supports')
            ->with($data)
            ->will($this->returnValue(false));

        $defaultPriorityApplicable->expects($this->once())
            ->method('supports')
            ->with($data)
            ->will($this->returnValue(true));

        $defaultPriorityApplicable->expects($this->once())
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
        $this->strategy->add($foo = $this->createStrategy('foo'));

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

    protected function createStrategy($name)
    {
        $result = $this->createMock('Oro\Bundle\EntityMergeBundle\Model\Strategy\StrategyInterface');
        $result->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($name));

        return $result;
    }

    protected function createFieldData()
    {
        return $this->getMockBuilder('Oro\Bundle\EntityMergeBundle\Data\FieldData')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
