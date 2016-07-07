<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Util;

use Oro\Component\MessageQueue\Tests\Unit\Util\Fixtures\JsonSerializableClass;
use Oro\Component\MessageQueue\Tests\Unit\Util\Fixtures\SimpleClass;
use Oro\Component\MessageQueue\Util\JSON;

class JSONTest extends \PHPUnit_Framework_TestCase
{
    public function testShouldDecodeString()
    {
        $this->assertSame(['foo' => 'fooVal'], JSON::decode('{"foo": "fooVal"}'));
    }

    public function testThrowIfMalformedJson()
    {
        $this->setExpectedException(\InvalidArgumentException::class, 'The malformed json given. ');
        $this->assertSame(['foo' => 'fooVal'], JSON::decode('{]'));
    }

    public function testShouldEncodeArray()
    {
        $this->assertEquals('{"key":"value"}', JSON::encode(['key' => 'value']));
    }

    public function testShouldEncodeString()
    {
        $this->assertEquals('"string"', JSON::encode('string'));
    }

    public function testShouldEncodeNumeric()
    {
        $this->assertEquals('123.45', JSON::encode(123.45));
    }

    public function testShouldEncodeNull()
    {
        $this->assertEquals('null', JSON::encode(null));
    }

    public function testShouldEncodeObjectOfStdClass()
    {
        $obj = new \stdClass();
        $obj->key = 'value';

        $this->assertEquals('{"key":"value"}', JSON::encode($obj));
    }

    public function testShouldEncodeObjectOfSimpleClass()
    {
        $this->assertEquals('{"keyPublic":"public"}', JSON::encode(new SimpleClass()));
    }

    public function testShouldEncodeObjectOfJsonSerializableClass()
    {
        $this->assertEquals('{"key":"value"}', JSON::encode(new JsonSerializableClass()));
    }

    public function testThrowIfValueIsResource()
    {
        $this->setExpectedException(
            \InvalidArgumentException::class,
            'Could not encode value into json. Error 8 and message Type is not supported'
        );

        $resource = fopen('php://memory', 'r');
        fclose($resource);

        JSON::encode($resource);
    }
}
