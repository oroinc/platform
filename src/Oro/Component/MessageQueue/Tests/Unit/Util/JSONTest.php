<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Util;

use Oro\Component\MessageQueue\Tests\Unit\Util\Fixtures\JsonSerializableClass;
use Oro\Component\MessageQueue\Tests\Unit\Util\Fixtures\SimpleClass;
use Oro\Component\MessageQueue\Util\JSON;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class JSONTest extends \PHPUnit\Framework\TestCase
{
    public function testShouldDecodeString(): void
    {
        self::assertSame(['foo' => 'fooVal'], JSON::decode('{"foo": "fooVal"}'));
    }

    public function testThrowIfMalformedJson(): void
    {
        $this->expectException(\JsonException::class);
        JSON::decode('{]');
    }

    public function nonStringDataProvider(): array
    {
        $resource = fopen('php://memory', 'r');
        fclose($resource);

        return [
            [null,],
            [true,],
            [false,],
            [new \stdClass(),],
            [123,],
            [123.45,],
            [$resource,],
        ];
    }

    public function testShouldReturnNullIfInputStringIsEmpty(): void
    {
        self::assertNull(JSON::decode(''));
    }

    public function testShouldEncodeArray(): void
    {
        self::assertEquals('{"key":"value"}', JSON::encode(['key' => 'value']));
    }

    public function testShouldEncodeString(): void
    {
        self::assertEquals('"string"', JSON::encode('string'));
    }

    public function testShouldEncodeNumeric(): void
    {
        self::assertEquals('123.45', JSON::encode(123.45));
    }

    public function testShouldEncodeNull(): void
    {
        self::assertEquals('null', JSON::encode(null));
    }

    public function testShouldEncodeObjectOfStdClass(): void
    {
        $obj = new \stdClass();
        $obj->key = 'value';

        self::assertEquals('{"key":"value"}', JSON::encode($obj));
    }

    public function testShouldEncodeObjectOfSimpleClass(): void
    {
        self::assertEquals('{"keyPublic":"public"}', JSON::encode(new SimpleClass()));
    }

    public function testShouldEncodeObjectOfJsonSerializableClass(): void
    {
        self::assertEquals('{"key":"value"}', JSON::encode(new JsonSerializableClass()));
    }

    public function testThrowIfValueIsResource(): void
    {
        $this->expectException(\JsonException::class);

        $resource = fopen('php://memory', 'r');
        fclose($resource);

        JSON::encode($resource);
    }
}
