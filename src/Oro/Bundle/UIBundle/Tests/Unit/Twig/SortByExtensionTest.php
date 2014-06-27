<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Twig;

use Oro\Bundle\UIBundle\Twig\SortByExtension;

class SortByExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var SortByExtension */
    private $extension;

    protected function setUp()
    {
        $this->extension = new SortByExtension();
    }

    public function testName()
    {
        $this->assertEquals('oro_sort_by', $this->extension->getName());
    }

    public function testSetFilters()
    {
        $filters = $this->extension->getFilters();
        $this->assertCount(1, $filters);
        $this->assertInstanceOf('\Twig_SimpleFilter', $filters[0]);
        $this->assertEquals('oro_sort_by', $filters[0]->getName());
        $callable = $filters[0]->getCallable();
        $this->assertTrue(is_array($callable), 'The filter callable should be an array');
        $this->assertCount(2, $callable);
        $this->assertSame($this->extension, $callable[0]);
        $this->assertEquals('sortBy', $callable[1]);
    }

    public function testSortByWithDefaultOptions()
    {
        $result = $this->extension->sortBy(
            [
                ['name' => '1'],
                ['name' => '2', 'priority' => 100],
                ['name' => '3'],
            ]
        );
        $this->assertSame(
            [
                ['name' => '1'],
                ['name' => '3'],
                ['name' => '2', 'priority' => 100],
            ],
            $result
        );
    }

    public function testSortByReverse()
    {
        $result = $this->extension->sortBy(
            [
                ['name' => '1'],
                ['name' => '2', 'priority' => 100],
                ['name' => '3'],
            ],
            [
                'reverse' => true
            ]
        );
        $this->assertSame(
            [
                ['name' => '2', 'priority' => 100],
                ['name' => '1'],
                ['name' => '3'],
            ],
            $result
        );
    }

    public function testSortByString()
    {
        $result = $this->extension->sortBy(
            [
                ['name' => 'a'],
                ['name' => 'c'],
                ['name' => 'b'],
            ],
            [
                'property'     => 'name',
                'sorting-type' => 'string'
            ]
        );
        $this->assertSame(
            [
                ['name' => 'a'],
                ['name' => 'b'],
                ['name' => 'c'],
            ],
            $result
        );
    }

    public function testSortByStringCaseInsensitive()
    {
        $result = $this->extension->sortBy(
            [
                ['name' => 'a'],
                ['name' => 'C'],
                ['name' => 'b'],
            ],
            [
                'property'     => 'name',
                'sorting-type' => 'string-case'
            ]
        );
        $this->assertSame(
            [
                ['name' => 'a'],
                ['name' => 'b'],
                ['name' => 'C'],
            ],
            $result
        );
    }
}
