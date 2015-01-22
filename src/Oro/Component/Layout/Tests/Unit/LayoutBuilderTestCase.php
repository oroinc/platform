<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\BlockView;

class LayoutBuilderTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @param array     $expected
     * @param BlockView $actual
     */
    protected function assertBlockView(array $expected, BlockView $actual)
    {
        $this->completeView($expected);
        $actualArray = $this->convertBlockViewToArray($actual);
        $this->assertEquals($expected, $actualArray);
    }

    /**
     * @param array $view
     *
     * @return array
     */
    protected function completeView(array &$view)
    {
        if (!isset($view['vars'])) {
            $view['vars'] = [];
        }
        if (!isset($view['vars']['attr'])) {
            $view['vars']['attr'] = [];
        }
        if (!isset($view['vars']['value'])) {
            $view['vars']['value'] = null;
        }
        if (!isset($view['children'])) {
            $view['children'] = [];
        }
        array_walk($view['children'], [$this, 'completeView']);
    }

    /**
     * @param BlockView $view
     *
     * @return array
     */
    protected function convertBlockViewToArray(BlockView $view)
    {
        return [
            'vars'     => $view->vars,
            'children' => array_map([$this, 'convertBlockViewToArray'], $view->children)
        ];
    }
}
