<?php

namespace Oro\Component\ChainProcessor\Tests\Unit;

use Oro\Component\ChainProcessor\ParameterBag;

class ParameterBagTest extends \PHPUnit\Framework\TestCase
{
    /** @var ParameterBag */
    private $parameterBag;

    protected function setUp(): void
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

    public function testRemove()
    {
        $key = 'test_key';

        $this->parameterBag->set($key, 'test_val');
        self::assertTrue($this->parameterBag->has($key));

        $this->parameterBag->remove($key);
        self::assertFalse($this->parameterBag->has($key));
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

        self::assertSame([], $this->parameterBag->toArray());

        $this->parameterBag->set($key1, $val1);
        $this->parameterBag->set($key2, $val2);
        self::assertSame([$key1 => $val1, $key2 => $val2], $this->parameterBag->toArray());
    }

    public function testIterator()
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
