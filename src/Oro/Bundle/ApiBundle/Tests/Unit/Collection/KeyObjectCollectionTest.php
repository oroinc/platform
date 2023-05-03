<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Collection;

use Oro\Bundle\ApiBundle\Collection\KeyObjectCollection;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class KeyObjectCollectionTest extends \PHPUnit\Framework\TestCase
{
    private KeyObjectCollection $collection;

    protected function setUp(): void
    {
        $this->collection = new KeyObjectCollection();
    }

    public function testShouldAddObjectWithoutData()
    {
        $this->collection->add(new \stdClass(), 'key');
        $this->expectNotToPerformAssertions();
    }

    public function testShouldAddObjectWithData()
    {
        $this->collection->add(new \stdClass(), 'key', 'data');
        $this->expectNotToPerformAssertions();
    }

    public function testShouldAddThrowExceptionForNullKey()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected $key argument of type "scalar", "null" given.');

        $this->collection->add(new \stdClass(), null);
    }

    public function testShouldAddThrowExceptionIfKeyIsObject()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected $key argument of type "scalar", "stdClass" given.');

        $this->collection->add(new \stdClass(), new \stdClass());
    }

    public function testShouldAddThrowExceptionIfKeyIsArray()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected $key argument of type "scalar", "array" given.');

        $this->collection->add(new \stdClass(), []);
    }

    /**
     * @dataProvider blankKeyProvider
     */
    public function testShouldAddThrowExceptionForBlankKey(string $key)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The $key argument should not be a blank string.');

        $this->collection->add(new \stdClass(), $key);
    }

    public function blankKeyProvider(): array
    {
        return [
            [''],
            [' ']
        ];
    }

    /**
     * @dataProvider validKeysProvider
     */
    public function testShouldAddWithNotStringKey(mixed $key)
    {
        $this->collection->add(new \stdClass(), $key);
        $this->expectNotToPerformAssertions();
    }

    public function validKeysProvider(): array
    {
        return [
            ['test'],
            [123],
            [1.23]
        ];
    }

    public function testShouldGetReturnNullForUnknownObject()
    {
        self::assertNull($this->collection->get('key'));
    }

    public function testShouldGetAddedObject()
    {
        $object = new \stdClass();
        $key = 'key';
        $this->collection->add($object, $key);
        self::assertSame($object, $this->collection->get($key));
    }

    public function testShouldGetKeyReturnNullForUnknownObject()
    {
        self::assertNull($this->collection->getKey(new \stdClass()));
    }

    public function testShouldGetKeyForAddedObject()
    {
        $object = new \stdClass();
        $key = 'key';
        $this->collection->add($object, $key);
        self::assertSame($key, $this->collection->getKey($object));
    }

    public function testShouldGetDataReturnNullForUnknownObject()
    {
        self::assertNull($this->collection->getData(new \stdClass()));
    }

    public function testShouldGetDataForAddedObject()
    {
        $object = new \stdClass();
        $key = 'key';
        $data = new \stdClass();
        $this->collection->add($object, $key, $data);
        self::assertSame($data, $this->collection->getData($object));
    }

    public function testShouldContainsKeyReturnFalseForUnknownObject()
    {
        self::assertFalse($this->collection->containsKey('key'));
    }

    public function testShouldContainsKeyReturnTrueForAddedObject()
    {
        $object = new \stdClass();
        $key = 'key';
        $this->collection->add($object, $key);
        self::assertTrue($this->collection->containsKey($key));
    }

    public function testShouldContainsReturnFalseForUnknownObject()
    {
        self::assertFalse($this->collection->contains(new \stdClass()));
    }

    public function testShouldContainsReturnTrueForAddedObject()
    {
        $object = new \stdClass();
        $key = 'key';
        $this->collection->add($object, $key);
        self::assertTrue($this->collection->contains($object));
    }

    public function testShouldGetAllReturnEmptyArrayForEmptyCollection()
    {
        self::assertSame([], $this->collection->getAll());
    }

    public function testShouldGetAllReturnAllObjects()
    {
        $object1 = new \stdClass();
        $object2 = new \stdClass();
        $key1 = 'key1';
        $key2 = 'key2';
        $this->collection->add($object1, $key1);
        $this->collection->add($object2, $key2);
        self::assertSame([$key1 => $object1, $key2 => $object2], $this->collection->getAll());
    }

    public function testShouldBeIterable()
    {
        $object = new \stdClass();
        $key = 'key';
        $this->collection->add($object, $key);
        foreach ($this->collection as $k => $v) {
            self::assertSame($key, $k);
            self::assertSame($object, $v);
        }
    }

    public function testShouldClearAllData()
    {
        $object = new \stdClass();
        $this->collection->add($object, 'key', 'data');
        $this->collection->clear();
        self::assertSame([], $this->collection->getAll());
        self::assertNull($this->collection->getKey($object));
        self::assertNull($this->collection->getData($object));
    }

    public function testShouldIsEmptyReturnTrueForEmptyCollection()
    {
        self::assertTrue($this->collection->isEmpty());
    }

    public function testShouldIsEmptyReturnFalseForEmptyCollection()
    {
        $this->collection->add(new \stdClass(), 'key', 'data');
        self::assertFalse($this->collection->isEmpty());
    }

    public function testShouldCountReturnZeroForEmptyCollection()
    {
        self::assertSame(0, $this->collection->count());
    }

    public function testShouldCountReturnTheNumberOfObjectsInCollection()
    {
        $this->collection->add(new \stdClass(), 'key', 'data');
        self::assertSame(1, $this->collection->count());
    }

    public function testShouldBeCountable()
    {
        self::assertCount(0, $this->collection);
    }

    public function testShouldRemoveNotThrowExceptionForUnknownObject()
    {
        $this->collection->remove(new \stdClass());
        $this->expectNotToPerformAssertions();
    }

    public function testShouldRemoveObject()
    {
        $object = new \stdClass();
        $this->collection->add($object, 'key', 'data');
        $this->collection->remove($object);
        self::assertSame([], $this->collection->getAll());
        self::assertNull($this->collection->getKey($object));
        self::assertNull($this->collection->getData($object));
    }

    public function testShouldRemoveKeyNotThrowExceptionForUnknownKey()
    {
        $this->collection->removeKey('key');
        $this->expectNotToPerformAssertions();
    }

    public function testShouldRemoveObjectByKey()
    {
        $object = new \stdClass();
        $this->collection->add($object, 'key', 'data');
        $this->collection->removeKey('key');
        self::assertSame([], $this->collection->getAll());
        self::assertNull($this->collection->getKey($object));
        self::assertNull($this->collection->getData($object));
    }
}
