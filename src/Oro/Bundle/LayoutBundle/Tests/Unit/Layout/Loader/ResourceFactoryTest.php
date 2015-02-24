<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Loader;

use Oro\Bundle\LayoutBundle\Layout\Loader\ResourceFactory;

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

        $result = $this->factory->create('somePath', $filename);

        $this->assertInstanceOf('Oro\Bundle\LayoutBundle\Layout\Loader\FileResource', $result);
        $this->assertSame($filename, $result->getFilename());

        $this->assertEmpty($result->getConditions());
    }

    public function testCreateWithRouteCondition()
    {
        $filename = uniqid('testFilename', true);

        $result = $this->factory->create('my_route/2', $filename);

        $this->assertInstanceOf('Oro\Bundle\LayoutBundle\Layout\Loader\FileResource', $result);
        $this->assertSame($filename, $result->getFilename());

        $this->assertNotEmpty($result->getConditions());
    }
}
