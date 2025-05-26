<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Collection;

use Oro\Bundle\ApiBundle\Collection\KeyObjectCollection;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class KeyObjectCollectionTest extends TestCase
{
    private KeyObjectCollection $collection;

    #[\Override]
    protected function setUp(): void
    {
        $this->collection = new KeyObjectCollection();
    }

    public function testShouldAddObjectWithoutData(): void
    {
        $this->collection->add(new \stdClass(), 'key');
        $this->expectNotToPerformAssertions();
    }

    public function testShouldAddObjectWithData(): void
    {
        $this->collection->add(new \stdClass(), 'key', 'data');
        $this->expectNotToPerformAssertions();
    }

    public function testShouldAddThrowExceptionForNullKey(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected $key argument of type "scalar", "null" given.');

        $this->collection->add(new \stdClass(), null);
    }

    public function testShouldAddThrowExceptionIfKeyIsObject(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected $key argument of type "scalar", "stdClass" given.');

        $this->collection->add(new \stdClass(), new \stdClass());
    }

    public function testShouldAddThrowExceptionIfKeyIsArray(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected $key argument of type "scalar", "array" given.');

        $this->collection->add(new \stdClass(), []);
    }

    /**
     * @dataProvider blankKeyProvider
     */
    public function testShouldAddThrowExceptionForBlankKey(string $key): void
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
    public function testShouldAddWithNotStringKey(mixed $key): void
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

    public function testShouldGetReturnNullForUnknownObject(): void
    {
        self::assertNull($this->collection->get('key'));
    }

    public function testShouldGetAddedObject(): void
    {
        $object = new \stdClass();
        $key = 'key';
        $this->collection->add($object, $key);
        self::assertSame($object, $this->collection->get($key));
    }

    public function testShouldGetKeyReturnNullForUnknownObject(): void
    {
        self::assertNull($this->collection->getKey(new \stdClass()));
    }

    public function testShouldGetKeyForAddedObject(): void
    {
        $object = new \stdClass();
        $key = 'key';
        $this->collection->add($object, $key);
        self::assertSame($key, $this->collection->getKey($object));
    }

    public function testShouldGetDataReturnNullForUnknownObject(): void
    {
        self::assertNull($this->collection->getData(new \stdClass()));
    }

    public function testShouldGetDataForAddedObject(): void
    {
        $object = new \stdClass();
        $key = 'key';
        $data = new \stdClass();
        $this->collection->add($object, $key, $data);
        self::assertSame($data, $this->collection->getData($object));
    }

    public function testShouldContainsKeyReturnFalseForUnknownObject(): void
    {
        self::assertFalse($this->collection->containsKey('key'));
    }

    public function testShouldContainsKeyReturnTrueForAddedObject(): void
    {
        $object = new \stdClass();
        $key = 'key';
        $this->collection->add($object, $key);
        self::assertTrue($this->collection->containsKey($key));
    }

    public function testShouldContainsReturnFalseForUnknownObject(): void
    {
        self::assertFalse($this->collection->contains(new \stdClass()));
    }

    public function testShouldContainsReturnTrueForAddedObject(): void
    {
        $object = new \stdClass();
        $key = 'key';
        $this->collection->add($object, $key);
        self::assertTrue($this->collection->contains($object));
    }

    public function testShouldGetAllReturnEmptyArrayForEmptyCollection(): void
    {
        self::assertSame([], $this->collection->getAll());
    }

    public function testShouldGetAllReturnAllObjects(): void
    {
        $object1 = new \stdClass();
        $object2 = new \stdClass();
        $key1 = 'key1';
        $key2 = 'key2';
        $this->collection->add($object1, $key1);
        $this->collection->add($object2, $key2);
        self::assertSame([$key1 => $object1, $key2 => $object2], $this->collection->getAll());
    }

    public function testShouldBeIterable(): void
    {
        $object = new \stdClass();
        $key = 'key';
        $this->collection->add($object, $key);
        foreach ($this->collection as $k => $v) {
            self::assertSame($key, $k);
            self::assertSame($object, $v);
        }
    }

    public function testShouldClearAllData(): void
    {
        $object = new \stdClass();
        $this->collection->add($object, 'key', 'data');
        $this->collection->clear();
        self::assertSame([], $this->collection->getAll());
        self::assertNull($this->collection->getKey($object));
        self::assertNull($this->collection->getData($object));
    }

    public function testShouldIsEmptyReturnTrueForEmptyCollection(): void
    {
        self::assertTrue($this->collection->isEmpty());
    }

    public function testShouldIsEmptyReturnFalseForEmptyCollection(): void
    {
        $this->collection->add(new \stdClass(), 'key', 'data');
        self::assertFalse($this->collection->isEmpty());
    }

    public function testShouldCountReturnZeroForEmptyCollection(): void
    {
        self::assertSame(0, $this->collection->count());
    }

    public function testShouldCountReturnTheNumberOfObjectsInCollection(): void
    {
        $this->collection->add(new \stdClass(), 'key', 'data');
        self::assertSame(1, $this->collection->count());
    }

    public function testShouldBeCountable(): void
    {
        self::assertCount(0, $this->collection);
    }

    public function testShouldRemoveNotThrowExceptionForUnknownObject(): void
    {
        $this->collection->remove(new \stdClass());
        $this->expectNotToPerformAssertions();
    }

    public function testShouldRemoveObject(): void
    {
        $object = new \stdClass();
        $this->collection->add($object, 'key', 'data');
        $this->collection->remove($object);
        self::assertSame([], $this->collection->getAll());
        self::assertNull($this->collection->getKey($object));
        self::assertNull($this->collection->getData($object));
    }

    public function testShouldRemoveKeyNotThrowExceptionForUnknownKey(): void
    {
        $this->collection->removeKey('key');
        $this->expectNotToPerformAssertions();
    }

    public function testShouldRemoveObjectByKey(): void
    {
        $object = new \stdClass();
        $this->collection->add($object, 'key', 'data');
        $this->collection->removeKey('key');
        self::assertSame([], $this->collection->getAll());
        self::assertNull($this->collection->getKey($object));
        self::assertNull($this->collection->getData($object));
    }
}
