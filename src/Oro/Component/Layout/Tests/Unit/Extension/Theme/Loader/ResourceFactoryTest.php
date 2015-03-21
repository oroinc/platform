<?php

namespace Oro\Component\Layout\Tests\Unit\Extension\Theme\Loader;

use Oro\Component\Layout\Extension\Theme\Loader\ResourceFactory;

class ResourceFactoryTest extends \PHPUnit_Framework_TestCase
{
    /** @var ResourceFactory */
    protected $factory;

    protected function setUp()
    {
        $this->factory = new ResourceFactory();
    }

    protected function tearDown()
    {
        unset($this->factory);
    }

    public function testCreate()
    {
        $filename = uniqid('testFilename', true);

        $result = $this->factory->create($filename);

        $this->assertInstanceOf('Oro\Component\Layout\Extension\Theme\Loader\FileResource', $result);
        $this->assertSame($filename, $result->getFilename());
    }
}
