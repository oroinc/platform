<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Request;

use Oro\Bundle\ApiBundle\Request\RequestType;

class RequestTypeTest extends \PHPUnit\Framework\TestCase
{
    public function testIsEmptyForEmptyRequestType()
    {
        $requestType = new RequestType([]);

        self::assertTrue($requestType->isEmpty());
    }

    public function testIsEmptyForNotEmptyRequestType()
    {
        $requestType = new RequestType([RequestType::REST]);

        self::assertFalse($requestType->isEmpty());
    }

    public function testClear()
    {
        $requestType = new RequestType([RequestType::REST]);

        self::assertEquals(RequestType::REST, (string)$requestType);
        $requestType->clear();
        self::assertTrue($requestType->isEmpty());
        self::assertEquals('', (string)$requestType);
    }

    public function testToString()
    {
        $requestType = new RequestType([RequestType::REST, RequestType::JSON_API]);

        self::assertEquals(RequestType::REST . ',' . RequestType::JSON_API, (string)$requestType);
        // test that internal state was not changed
        self::assertEquals([RequestType::REST, RequestType::JSON_API], $requestType->toArray());
    }

    public function testToStringShouldSortAspectsBeforeBuildStringRepresentation()
    {
        $requestType = new RequestType([RequestType::JSON_API, RequestType::REST]);

        self::assertEquals(RequestType::REST . ',' . RequestType::JSON_API, (string)$requestType);
        // test that internal state was not changed
        self::assertEquals([RequestType::JSON_API, RequestType::REST], $requestType->toArray());
    }

    public function testToArray()
    {
        $requestType = new RequestType([RequestType::REST, RequestType::JSON_API]);

        self::assertEquals([RequestType::REST, RequestType::JSON_API], $requestType->toArray());
    }

    public function testContains()
    {
        $requestType = new RequestType([RequestType::REST]);

        self::assertTrue($requestType->contains(RequestType::REST));
        self::assertFalse($requestType->contains('another'));
    }

    public function testAdd()
    {
        $requestType = new RequestType([RequestType::REST]);

        self::assertEquals(RequestType::REST, (string)$requestType);
        $requestType->add(RequestType::JSON_API);
        self::assertEquals(RequestType::REST . ',' . RequestType::JSON_API, (string)$requestType);
        self::assertEquals([RequestType::REST, RequestType::JSON_API], $requestType->toArray());
    }

    public function testAddDuplicate()
    {
        $requestType = new RequestType([RequestType::REST]);

        self::assertEquals(RequestType::REST, (string)$requestType);
        $requestType->add(RequestType::REST);
        self::assertEquals(RequestType::REST, (string)$requestType);
        self::assertEquals([RequestType::REST], $requestType->toArray());
    }

    public function testRemove()
    {
        $requestType = new RequestType([RequestType::REST, RequestType::JSON_API]);

        self::assertEquals(RequestType::REST . ',' . RequestType::JSON_API, (string)$requestType);
        $requestType->remove(RequestType::REST);
        self::assertEquals(RequestType::JSON_API, (string)$requestType);
        self::assertEquals([RequestType::JSON_API], $requestType->toArray());
    }

    public function testRemoveUnknown()
    {
        $requestType = new RequestType([RequestType::REST, RequestType::JSON_API]);

        self::assertEquals(RequestType::REST . ',' . RequestType::JSON_API, (string)$requestType);
        $requestType->remove('another');
        self::assertEquals(RequestType::REST . ',' . RequestType::JSON_API, (string)$requestType);
        self::assertEquals([RequestType::REST, RequestType::JSON_API], $requestType->toArray());
    }

    public function testSet()
    {
        $requestType = new RequestType([RequestType::REST, RequestType::JSON_API]);

        self::assertEquals(RequestType::REST . ',' . RequestType::JSON_API, (string)$requestType);
        $requestType->set(new RequestType([RequestType::REST]));
        self::assertEquals(RequestType::REST, (string)$requestType);
        self::assertEquals([RequestType::REST], $requestType->toArray());
    }
}
