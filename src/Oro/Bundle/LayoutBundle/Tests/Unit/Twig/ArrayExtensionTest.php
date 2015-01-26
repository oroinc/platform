<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Twig;

use Oro\Bundle\LayoutBundle\Twig\ArrayExtension;

class ArrayExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var ArrayExtension */
    protected $extension;

    protected function setUp()
    {
        $this->extension = new ArrayExtension();
    }

    public function testGetName()
    {
        $this->assertEquals('oro_layout_array', $this->extension->getName());
    }

    public function testGetFilters()
    {
        $filters = $this->extension->getFilters();

        $this->assertCount(1, $filters);

        $this->assertInstanceOf('Twig_SimpleFilter', $filters[0]);
        /** @var \Twig_SimpleFilter $filter */
        $filter = $filters[0];
        $this->assertEquals('omit', $filter->getName());
        $this->assertSame([$this->extension, 'omitFilter'], $filter->getCallable());
    }

    public function testOmitFilter()
    {
        $result = $this->extension->omitFilter(
            ['key1' => 'val1', 'key2' => 'val2', 'key3' => 'val3', 'key4' => 'val4'],
            ['key1', 'key3']
        );
        $this->assertSame(
            ['key2' => 'val2', 'key4' => 'val4'],
            $result
        );
    }

    public function testOmitFilterForNumberKeys()
    {
        $result = $this->extension->omitFilter(
            ['val1', 'val2', 'val3', 'val4'],
            [0, 2]
        );
        $this->assertSame(
            [1 => 'val2', 3 => 'val4'],
            $result
        );
    }

    /**
     * @expectedException \Twig_Error_Runtime
     * @expectedExceptionMessage The omit filter only works with arrays or hashes.
     */
    public function testOmitFilterFirstArgumentIsNotArray()
    {
        $this->extension->omitFilter(
            'test',
            ['key1', 'key3']
        );
    }

    /**
     * @expectedException \Twig_Error_Runtime
     * @expectedExceptionMessage The omit filter only works with arrays or hashes.
     */
    public function testOmitFilterSecondArgumentIsNotArray()
    {
        $this->extension->omitFilter(
            ['key1' => 'val1', 'key2' => 'val2', 'key3' => 'val3', 'key4' => 'val4'],
            null
        );
    }
}
