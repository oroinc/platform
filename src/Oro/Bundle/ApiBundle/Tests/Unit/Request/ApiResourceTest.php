<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Request;

use Oro\Bundle\ApiBundle\Request\ApiResource;

class ApiResourceTest extends \PHPUnit\Framework\TestCase
{
    public function testGetEntityClass()
    {
        $className = 'Test\Class';

        $resource = new ApiResource($className);
        self::assertEquals($className, $resource->getEntityClass());
    }

    public function testExcludedActions()
    {
        $resource = new ApiResource('Test\Class');
        self::assertEquals([], $resource->getExcludedActions());

        $excludedActions = ['delete', 'delete_list'];
        $resource->setExcludedActions($excludedActions);
        self::assertEquals($excludedActions, $resource->getExcludedActions());
    }
}
