<?php

namespace Oro\Component\Config\Tests\Unit;

use Oro\Component\Config\CumulativeResourceInfo;

class CumulativeResourceInfoTest extends \PHPUnit\Framework\TestCase
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
