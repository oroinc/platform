<?php

namespace Oro\Component\ChainProcessor\Tests\Unit;

use Oro\Component\ChainProcessor\ParameterBag;
use PHPUnit\Framework\TestCase;

class ParameterBagTest extends TestCase
{
    private ParameterBag $parameterBag;

    #[\Override]
    protected function setUp(): void
    {
        $this->parameterBag = new ParameterBag();
    }

    public function testHas(): void
    {
        $key = 'test_key';

        self::assertFalse($this->parameterBag->has($key));
        self::assertFalse(isset($this->parameterBag[$key]));

        $this->parameterBag->set($key, null);
        self::assertTrue($this->parameterBag->has($key));
        self::assertTrue(isset($this->parameterBag[$key]));
    }

    public function testGetNotExistingValue(): void
    {
        $key = 'test_key';

        self::assertNull($this->parameterBag->get($key));
        self::assertNull($this->parameterBag[$key]);
    }

    public function testGetExistingValue(): void
    {
        $key = 'test_key';
        $val = 'test_val';

        $this->parameterBag->set($key, $val);
        self::assertSame($val, $this->parameterBag->get($key));
        self::assertSame($val, $this->parameterBag[$key]);
    }

    public function testGetExistingNullValue(): void
    {
        $key = 'test_key';

        $this->parameterBag->set($key, null);
        self::assertNull($this->parameterBag->get($key));
        self::assertNull($this->parameterBag[$key]);
    }

    public function testRemove(): void
    {
        $key = 'test_key';

        $this->parameterBag->set($key, 'test_val');
        self::assertTrue($this->parameterBag->has($key));

        $this->parameterBag->remove($key);
        self::assertFalse($this->parameterBag->has($key));
    }

    public function testArraySetAndUnset(): void
    {
        $key = 'test_key';

        $this->parameterBag[$key] = 'test_val';
        self::assertTrue($this->parameterBag->has($key));

        unset($this->parameterBag[$key]);
        self::assertFalse($this->parameterBag->has($key));
    }

    public function testClear(): void
    {
        $key = 'test_key';

        $this->parameterBag->set($key, 'test_val');
        self::assertTrue($this->parameterBag->has($key));

        $this->parameterBag->clear();
        self::assertFalse($this->parameterBag->has($key));
    }

    public function testCount(): void
    {
        $key = 'test_key';
        $val = 'test_val';

        self::assertSame(0, $this->parameterBag->count());

        $this->parameterBag->set($key, $val);
        self::assertSame(1, $this->parameterBag->count());
    }

    public function testToArray(): void
    {
        $key1 = 'test_key1';
        $val1 = 'test_val1';
        $key2 = 'test_key2';
        $val2 = 'test_val2';

        self::assertSame([], $this->parameterBag->toArray());

        $this->parameterBag->set($key1, $val1);
        $this->parameterBag->set($key2, $val2);
        self::assertSame([$key1 => $val1, $key2 => $val2], $this->parameterBag->toArray());
    }

    public function testIterator(): void
    {
        $key1 = 'test_key1';
        $val1 = 'test_val1';
        $key2 = 'test_key2';
        $val2 = 'test_val2';

        $this->parameterBag->set($key1, $val1);
        $this->parameterBag->set($key2, $val2);
        self::assertSame([$key1 => $val1, $key2 => $val2], iterator_to_array($this->parameterBag));
    }
}
