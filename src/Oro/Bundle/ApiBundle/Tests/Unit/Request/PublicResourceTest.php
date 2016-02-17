<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Request;

use Oro\Bundle\ApiBundle\Request\PublicResource;

class PublicResourceTest extends \PHPUnit_Framework_TestCase
{
    public function testGetEntityClass()
    {
        $className = 'Test\Class';

        $resource = new PublicResource($className);
        $this->assertEquals($className, $resource->getEntityClass());
    }

    public function testToString()
    {
        $className = 'Test\Class';

        $resource = new PublicResource($className);
        $this->assertEquals($className, (string)$resource);
    }
}
