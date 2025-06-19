<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Request;

use Oro\Bundle\ApiBundle\Request\RequestType;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class RequestTypeTest extends TestCase
{
    public function testIsEmptyForEmptyRequestType(): void
    {
        $requestType = new RequestType([]);

        self::assertTrue($requestType->isEmpty());
    }

    public function testIsEmptyForNotEmptyRequestType(): void
    {
        $requestType = new RequestType([RequestType::REST]);

        self::assertFalse($requestType->isEmpty());
    }

    public function testClear(): void
    {
        $requestType = new RequestType([RequestType::REST]);

        self::assertEquals(RequestType::REST, (string)$requestType);
        $requestType->clear();
        self::assertTrue($requestType->isEmpty());
        self::assertEquals('', (string)$requestType);
    }

    public function testToString(): void
    {
        $requestType = new RequestType([RequestType::REST, RequestType::JSON_API]);

        self::assertEquals(RequestType::REST . ',' . RequestType::JSON_API, (string)$requestType);
        // test that internal state was not changed
        self::assertEquals([RequestType::REST, RequestType::JSON_API], $requestType->toArray());
    }

    public function testToStringShouldSortAspectsBeforeBuildStringRepresentation(): void
    {
        $requestType = new RequestType([RequestType::JSON_API, RequestType::REST]);

        self::assertEquals(RequestType::REST . ',' . RequestType::JSON_API, (string)$requestType);
        // test that internal state was not changed
        self::assertEquals([RequestType::JSON_API, RequestType::REST], $requestType->toArray());
    }

    public function testToArray(): void
    {
        $requestType = new RequestType([RequestType::REST, RequestType::JSON_API]);

        self::assertEquals([RequestType::REST, RequestType::JSON_API], $requestType->toArray());
    }

    public function testContains(): void
    {
        $requestType = new RequestType([RequestType::REST]);

        self::assertTrue($requestType->contains(RequestType::REST));
        self::assertFalse($requestType->contains('another'));
    }

    public function testAdd(): void
    {
        $requestType = new RequestType([RequestType::REST]);

        self::assertEquals(RequestType::REST, (string)$requestType);
        $requestType->add(RequestType::JSON_API);
        self::assertEquals(RequestType::REST . ',' . RequestType::JSON_API, (string)$requestType);
        self::assertEquals([RequestType::REST, RequestType::JSON_API], $requestType->toArray());
    }

    public function testAddDuplicate(): void
    {
        $requestType = new RequestType([RequestType::REST]);

        self::assertEquals(RequestType::REST, (string)$requestType);
        $requestType->add(RequestType::REST);
        self::assertEquals(RequestType::REST, (string)$requestType);
        self::assertEquals([RequestType::REST], $requestType->toArray());
    }

    public function testRemove(): void
    {
        $requestType = new RequestType([RequestType::REST, RequestType::JSON_API]);

        self::assertEquals(RequestType::REST . ',' . RequestType::JSON_API, (string)$requestType);
        $requestType->remove(RequestType::REST);
        self::assertEquals(RequestType::JSON_API, (string)$requestType);
        self::assertEquals([RequestType::JSON_API], $requestType->toArray());
    }

    public function testRemoveUnknown(): void
    {
        $requestType = new RequestType([RequestType::REST, RequestType::JSON_API]);

        self::assertEquals(RequestType::REST . ',' . RequestType::JSON_API, (string)$requestType);
        $requestType->remove('another');
        self::assertEquals(RequestType::REST . ',' . RequestType::JSON_API, (string)$requestType);
        self::assertEquals([RequestType::REST, RequestType::JSON_API], $requestType->toArray());
    }

    public function testSet(): void
    {
        $requestType = new RequestType([RequestType::REST, RequestType::JSON_API]);

        self::assertEquals(RequestType::REST . ',' . RequestType::JSON_API, (string)$requestType);
        $requestType->set(new RequestType([RequestType::REST]));
        self::assertEquals(RequestType::REST, (string)$requestType);
        self::assertEquals([RequestType::REST], $requestType->toArray());
    }
}
