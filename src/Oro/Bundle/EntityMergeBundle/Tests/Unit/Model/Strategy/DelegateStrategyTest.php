<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Model\Strategy;

use Oro\Bundle\EntityMergeBundle\Data\FieldData;
use Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException;
use Oro\Bundle\EntityMergeBundle\Model\Strategy\DelegateStrategy;
use Oro\Bundle\EntityMergeBundle\Model\Strategy\StrategyInterface;

class DelegateStrategyTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @return StrategyInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createStrategy(string $name)
    {
        $result = $this->createMock(StrategyInterface::class);
        $result->expects($this->any())
            ->method('getName')
            ->willReturn($name);

        return $result;
    }

    public function testSupportsTrueLast()
    {
        $data = $this->createMock(FieldData::class);

        $foo = $this->createStrategy('foo');
        $foo->expects($this->once())
            ->method('supports')
            ->with($data)
            ->willReturn(false);

        $bar = $this->createStrategy('bar');
        $bar->expects($this->once())
            ->method('supports')
            ->with($data)
            ->willReturn(false);

        $baz = $this->createStrategy('baz');
        $baz->expects($this->once())
            ->method('supports')
            ->with($data)
            ->willReturn(true);

        $strategy = new DelegateStrategy([$foo, $bar, $baz]);
        $this->assertTrue($strategy->supports($data));
    }

    public function testSupportsTrueFirst()
    {
        $data = $this->createMock(FieldData::class);

        $foo = $this->createStrategy('foo');
        $foo->expects($this->once())
            ->method('supports')
            ->with($data)
            ->willReturn(true);

        $bar = $this->createStrategy('bar');
        $bar->expects($this->never())
            ->method('supports');

        $strategy = new DelegateStrategy([$foo, $bar]);
        $this->assertTrue($strategy->supports($data));
    }

    public function testSupportsFalse()
    {
        $data = $this->createMock(FieldData::class);

        $foo = $this->createStrategy('foo');
        $foo->expects($this->once())
            ->method('supports')
            ->with($data)
            ->willReturn(false);

        $bar = $this->createStrategy('bar');
        $bar->expects($this->once())
            ->method('supports')
            ->with($data)
            ->willReturn(false);

        $strategy = new DelegateStrategy([$foo, $bar]);
        $this->assertFalse($strategy->supports($data));
    }

    public function testMerge()
    {
        $data = $this->createMock(FieldData::class);

        $highPriorityNotApplicable = $this->createStrategy('highPriorityNotApplicable');
        $highPriorityNotApplicable->expects($this->once())
            ->method('supports')
            ->with($data)
            ->willReturn(false);

        $defaultPriorityApplicable = $this->createStrategy('defaultPriorityApplicable');
        $defaultPriorityApplicable->expects($this->once())
            ->method('supports')
            ->with($data)
            ->willReturn(true);
        $defaultPriorityApplicable->expects($this->once())
            ->method('merge')
            ->with($data);

        $lowPriorityApplicable = $this->createStrategy('lowPriorityApplicable');
        $lowPriorityApplicable->expects($this->never())
            ->method('supports');

        $strategy = new DelegateStrategy([
            $highPriorityNotApplicable,
            $defaultPriorityApplicable,
            $lowPriorityApplicable
        ]);
        $strategy->merge($data);
    }

    public function testMergeFails()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot find merge strategy for "test" field.');

        $data = $this->createMock(FieldData::class);
        $data->expects($this->once())
            ->method('getFieldName')
            ->willReturn('test');

        $foo = $this->createStrategy('foo');
        $foo->expects($this->once())
            ->method('supports')
            ->with($data)
            ->willReturn(false);

        $strategy = new DelegateStrategy([$foo]);
        $strategy->merge($data);
    }
}
