<?php

namespace Oro\Component\ChainProcessor\Tests\Unit;

use Oro\Component\ChainProcessor\ParameterBag;
use Oro\Component\ChainProcessor\ParameterValueResolverInterface;

class ParameterBagTest extends \PHPUnit\Framework\TestCase
{
    /** @var ParameterBag */
    private $parameterBag;

    protected function setUp()
    {
        $this->parameterBag = new ParameterBag();
    }

    public function testHas()
    {
        $key = 'test_key';

        self::assertFalse($this->parameterBag->has($key));
        self::assertFalse(isset($this->parameterBag[$key]));

        $this->parameterBag->set($key, null);
        self::assertTrue($this->parameterBag->has($key));
        self::assertTrue(isset($this->parameterBag[$key]));
    }

    public function testGetNotExistingValue()
    {
        $key = 'test_key';

        self::assertNull($this->parameterBag->get($key));
        self::assertNull($this->parameterBag[$key]);
    }

    public function testGetExistingValue()
    {
        $key = 'test_key';
        $val = 'test_val';

        $this->parameterBag->set($key, $val);
        self::assertSame($val, $this->parameterBag->get($key));
        self::assertSame($val, $this->parameterBag[$key]);
    }

    public function testGetExistingNullValue()
    {
        $key = 'test_key';

        $this->parameterBag->set($key, null);
        self::assertNull($this->parameterBag->get($key));
        self::assertNull($this->parameterBag[$key]);
    }

    public function testGetLazyValue()
    {
        $key = 'test_key';
        $val = 'test_val';
        $resolvedVal = 'test_val_resolved';
        $resolver = $this->createMock(ParameterValueResolverInterface::class);
        $resolver->expects(self::once())
            ->method('supports')
            ->with($val)
            ->willReturn(true);
        $resolver->expects(self::once())
            ->method('resolve')
            ->with($val)
            ->willReturn($resolvedVal);

        $this->parameterBag->set($key, $val);
        $this->parameterBag->setResolver($key, $resolver);
        self::assertSame($resolvedVal, $this->parameterBag->get($key));
        self::assertSame($resolvedVal, $this->parameterBag[$key]);
        // test that value is resolved only once
        self::assertSame($resolvedVal, $this->parameterBag->get($key));
    }

    public function testGetLazyValueWhenResolverDoesNotSupportIt()
    {
        $key = 'test_key';
        $val = 'test_val';
        $resolver = $this->createMock(ParameterValueResolverInterface::class);
        $resolver->expects(self::once())
            ->method('supports')
            ->with($val)
            ->willReturn(false);
        $resolver->expects(self::never())
            ->method('resolve');

        $this->parameterBag->set($key, $val);
        $this->parameterBag->setResolver($key, $resolver);
        self::assertSame($val, $this->parameterBag->get($key));
        self::assertSame($val, $this->parameterBag[$key]);
        // test that value is resolved only once
        self::assertSame($val, $this->parameterBag->get($key));
    }

    public function testRemove()
    {
        $key = 'test_key';

        $this->parameterBag->set($key, 'test_val');
        self::assertTrue($this->parameterBag->has($key));

        $this->parameterBag->remove($key);
        self::assertFalse($this->parameterBag->has($key));
    }

    public function testRemoveLazyValue()
    {
        $key = 'test_key';
        $val1 = 'test_val1';
        $resolvedVal1 = 'test_val1_resolved';
        $val2 = 'test_val2';
        $resolvedVal2 = 'test_val2_resolved';
        $resolver = $this->createMock(ParameterValueResolverInterface::class);
        $resolver->expects(self::exactly(2))
            ->method('supports')
            ->willReturnMap([
                [$val1, true],
                [$val2, true]
            ]);
        $resolver->expects(self::exactly(2))
            ->method('resolve')
            ->willReturnMap([
                [$val1, $resolvedVal1],
                [$val2, $resolvedVal2]
            ]);

        $this->parameterBag->setResolver($key, $resolver);

        $this->parameterBag->set($key, $val1);
        self::assertSame($resolvedVal1, $this->parameterBag->get($key));

        $this->parameterBag->remove($key);
        self::assertFalse($this->parameterBag->has($key));

        // test that new value is re-computed
        $this->parameterBag->set($key, $val2);
        self::assertSame($resolvedVal2, $this->parameterBag->get($key));
    }

    public function testSetLazyValue()
    {
        $key = 'test_key';
        $val1 = 'test_val1';
        $resolvedVal1 = 'test_val1_resolved';
        $val2 = 'test_val2';
        $resolvedVal2 = 'test_val2_resolved';
        $resolver = $this->createMock(ParameterValueResolverInterface::class);
        $resolver->expects(self::exactly(2))
            ->method('supports')
            ->willReturnMap([
                [$val1, true],
                [$val2, true]
            ]);
        $resolver->expects(self::exactly(2))
            ->method('resolve')
            ->willReturnMap([
                [$val1, $resolvedVal1],
                [$val2, $resolvedVal2]
            ]);

        $this->parameterBag->setResolver($key, $resolver);

        $this->parameterBag->set($key, $val1);
        self::assertSame($resolvedVal1, $this->parameterBag->get($key));

        // test that new value is re-computed
        $this->parameterBag->set($key, $val2);
        self::assertSame($resolvedVal2, $this->parameterBag->get($key));
    }

    public function testArraySetAndUnset()
    {
        $key = 'test_key';

        $this->parameterBag[$key] = 'test_val';
        self::assertTrue($this->parameterBag->has($key));

        unset($this->parameterBag[$key]);
        self::assertFalse($this->parameterBag->has($key));
    }

    public function testClear()
    {
        $key = 'test_key';

        $this->parameterBag->set($key, 'test_val');
        self::assertTrue($this->parameterBag->has($key));

        $this->parameterBag->clear();
        self::assertFalse($this->parameterBag->has($key));
    }

    public function testCount()
    {
        $key = 'test_key';
        $val = 'test_val';

        self::assertSame(0, $this->parameterBag->count());

        $this->parameterBag->set($key, $val);
        self::assertSame(1, $this->parameterBag->count());
    }

    public function testToArray()
    {
        $key1 = 'test_key1';
        $val1 = 'test_val1';
        $key2 = 'test_key2';
        $val2 = 'test_val2';
        $resolvedVal2 = 'test_val2_resolved';

        $resolver = $this->createMock(ParameterValueResolverInterface::class);
        $resolver->expects(self::once())
            ->method('supports')
            ->with($val2)
            ->willReturn(true);
        $resolver->expects(self::once())
            ->method('resolve')
            ->with($val2)
            ->willReturn($resolvedVal2);

        self::assertSame([], $this->parameterBag->toArray());

        $this->parameterBag->set($key1, $val1);
        $this->parameterBag->set($key2, $val2);
        $this->parameterBag->setResolver($key2, $resolver);
        self::assertSame([$key1 => $val1, $key2 => $resolvedVal2], $this->parameterBag->toArray());
        // test that values are resolved only once
        self::assertSame([$key1 => $val1, $key2 => $resolvedVal2], $this->parameterBag->toArray());
    }

    public function testIterator()
    {
        $key1 = 'test_key1';
        $val1 = 'test_val1';
        $key2 = 'test_key2';
        $val2 = 'test_val2';
        $resolvedVal2 = 'test_val2_resolved';

        $resolver = $this->createMock(ParameterValueResolverInterface::class);
        $resolver->expects(self::once())
            ->method('supports')
            ->with($val2)
            ->willReturn(true);
        $resolver->expects(self::once())
            ->method('resolve')
            ->with($val2)
            ->willReturn($resolvedVal2);

        $this->parameterBag->set($key1, $val1);
        $this->parameterBag->set($key2, $val2);
        $this->parameterBag->setResolver($key2, $resolver);
        self::assertSame([$key1 => $val1, $key2 => $resolvedVal2], iterator_to_array($this->parameterBag));
        // test that values are resolved only once
        self::assertSame([$key1 => $val1, $key2 => $resolvedVal2], iterator_to_array($this->parameterBag));
    }
}
