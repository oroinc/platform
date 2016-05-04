<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Request;

use Oro\Bundle\ApiBundle\Request\ApiResource;

class ApiResourceTest extends \PHPUnit_Framework_TestCase
{
    public function testGetEntityClass()
    {
        $className = 'Test\Class';

        $resource = new ApiResource($className);
        $this->assertEquals($className, $resource->getEntityClass());
    }

    public function testToString()
    {
        $className = 'Test\Class';

        $resource = new ApiResource($className);
        $this->assertEquals($className, (string)$resource);
    }
}
