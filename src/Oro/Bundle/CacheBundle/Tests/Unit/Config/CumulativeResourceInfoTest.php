<?php

namespace Oro\Bundle\CacheBundle\Tests\Unit\Config;

use Oro\Bundle\CacheBundle\Config\CumulativeResourceInfo;

class CumulativeResourceInfoTest extends \PHPUnit_Framework_TestCase
{
    public function testConfig()
    {
        $bundleClass = 'bundleClass';
        $name        = 'name';
        $path        = 'path';
        $data        = ['test' => 123];

        $resource = new CumulativeResourceInfo($bundleClass, $name, $path, $data);

        $this->assertEquals($bundleClass, $resource->bundleClass);
        $this->assertEquals($name, $resource->name);
        $this->assertEquals($path, $resource->path);
        $this->assertEquals($data, $resource->data);
    }
}
