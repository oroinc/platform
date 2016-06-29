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

    public function testExcludedActions()
    {
        $resource = new ApiResource('Test\Class');
        $this->assertEquals([], $resource->getExcludedActions());

        $excludedActions = ['delete', 'delete_list'];
        $resource->setExcludedActions($excludedActions);
        $this->assertEquals($excludedActions, $resource->getExcludedActions());
    }
}
