<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Request;

use Oro\Bundle\ApiBundle\Request\RequestType;

class RequestTypeTest extends \PHPUnit_Framework_TestCase
{
    public function testIsEmptyForEmptyRequestType()
    {
        $requestType = new RequestType([]);

        $this->assertTrue($requestType->isEmpty());
    }

    public function testIsEmptyForNotEmptyRequestType()
    {
        $requestType = new RequestType([RequestType::REST]);

        $this->assertFalse($requestType->isEmpty());
    }

    public function testClear()
    {
        $requestType = new RequestType([RequestType::REST]);

        $this->assertEquals(RequestType::REST, (string)$requestType);
        $requestType->clear();
        $this->assertTrue($requestType->isEmpty());
        $this->assertEquals('', (string)$requestType);
    }

    public function testToString()
    {
        $requestType = new RequestType([RequestType::REST, RequestType::JSON_API]);

        $this->assertEquals(RequestType::REST . ',' . RequestType::JSON_API, (string)$requestType);
    }

    public function testToArray()
    {
        $requestType = new RequestType([RequestType::REST, RequestType::JSON_API]);

        $this->assertEquals([RequestType::REST, RequestType::JSON_API], $requestType->toArray());
    }

    public function testContains()
    {
        $requestType = new RequestType([RequestType::REST]);

        $this->assertTrue($requestType->contains(RequestType::REST));
        $this->assertFalse($requestType->contains('another'));
    }

    public function testAdd()
    {
        $requestType = new RequestType([RequestType::REST]);

        $this->assertEquals(RequestType::REST, (string)$requestType);
        $requestType->add(RequestType::JSON_API);
        $this->assertEquals(RequestType::REST . ',' . RequestType::JSON_API, (string)$requestType);
        $this->assertEquals([RequestType::REST, RequestType::JSON_API], $requestType->toArray());
    }

    public function testAddDuplicate()
    {
        $requestType = new RequestType([RequestType::REST]);

        $this->assertEquals(RequestType::REST, (string)$requestType);
        $requestType->add(RequestType::REST);
        $this->assertEquals(RequestType::REST, (string)$requestType);
        $this->assertEquals([RequestType::REST], $requestType->toArray());
    }

    public function testRemove()
    {
        $requestType = new RequestType([RequestType::REST, RequestType::JSON_API]);

        $this->assertEquals(RequestType::REST . ',' . RequestType::JSON_API, (string)$requestType);
        $requestType->remove(RequestType::REST);
        $this->assertEquals(RequestType::JSON_API, (string)$requestType);
        $this->assertEquals([RequestType::JSON_API], $requestType->toArray());
    }

    public function testRemoveUnknown()
    {
        $requestType = new RequestType([RequestType::REST, RequestType::JSON_API]);

        $this->assertEquals(RequestType::REST . ',' . RequestType::JSON_API, (string)$requestType);
        $requestType->remove('another');
        $this->assertEquals(RequestType::REST . ',' . RequestType::JSON_API, (string)$requestType);
        $this->assertEquals([RequestType::REST, RequestType::JSON_API], $requestType->toArray());
    }

    public function testSet()
    {
        $requestType = new RequestType([RequestType::REST, RequestType::JSON_API]);

        $this->assertEquals(RequestType::REST . ',' . RequestType::JSON_API, (string)$requestType);
        $requestType->set(new RequestType([RequestType::REST]));
        $this->assertEquals(RequestType::REST, (string)$requestType);
        $this->assertEquals([RequestType::REST], $requestType->toArray());
    }
}
