<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Placeholder\Filter;

use Oro\Bundle\UIBundle\Placeholder\Filter\Sorter;

class SorterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Sorter
     */
    protected $filter;

    protected function setUp()
    {
        $this->filter = new Sorter();
    }

    /**
     * @dataProvider filterDataProvider
     */
    public function testFilter($actual, $expected)
    {
        $variables = array('foo' => 'bar');
        $this->assertEquals(
            $expected,
            $this->filter->filter($actual, $variables)
        );
    }

    public function filterDataProvider()
    {
        return array(
            'sort' => array(
                'actual' => array(
                    array('template' => 'foo_200', 'order' => 200),
                    array('template' => 'bar'),
                    array('template' => 'baz_100', 'order' => 100),
                ),
                'expected' => array(
                    array('template' => 'bar'),
                    array('template' => 'baz_100', 'order' => 100),
                    array('template' => 'foo_200', 'order' => 200),
                ),
            ),
            'empty' => array(
                'actual' => array(),
                'expected' => array(),
            ),
        );
    }
}
