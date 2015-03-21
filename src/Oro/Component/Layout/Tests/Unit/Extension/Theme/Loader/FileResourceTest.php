<?php

namespace Oro\Component\Layout\Tests\Unit\Extension\Theme\Loader;

use Oro\Component\Layout\Extension\Theme\Loader\FileResource;

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
