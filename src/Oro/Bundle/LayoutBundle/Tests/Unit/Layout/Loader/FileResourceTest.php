<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Loader;

use Oro\Bundle\LayoutBundle\Layout\Loader\FileResource;

class FileResourceTest extends \PHPUnit_Framework_TestCase
{
    public function testResourceInterface()
    {
        $filename = uniqid('testFilename', true);
        $resource = new FileResource($filename);

        $this->assertSame($filename, $resource->getFilename());
        $this->assertSame($filename, (string)$resource);
    }
}
