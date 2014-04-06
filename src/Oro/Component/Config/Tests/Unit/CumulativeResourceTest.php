<?php

namespace Oro\Component\Config\Tests\Unit;

use Oro\Component\Config\CumulativeResource;

class CumulativeResourceTest extends \PHPUnit_Framework_TestCase
{
    public function testResource()
    {
        $resource = new CumulativeResource('test');
        $this->assertEquals('test', $resource->getResource());
        $this->assertEquals('test', $resource->__toString());
    }

    public function testSetialization()
    {
        $resource = new CumulativeResource('test');
        $serializedData = $resource->serialize();
        $unserializedResource = new CumulativeResource('test1');
        $unserializedResource->unserialize($serializedData);

        $this->assertEquals($resource, $unserializedResource);
    }
}
