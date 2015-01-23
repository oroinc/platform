<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\BlockView;

class LayoutBuilderTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @param array     $expected
     * @param BlockView $actual
     * @param bool      $ignoreAuxiliaryVariables
     */
    protected function assertBlockView(array $expected, BlockView $actual, $ignoreAuxiliaryVariables = true)
    {
        $this->completeView($expected);
        $actualArray = $this->convertBlockViewToArray($actual, $ignoreAuxiliaryVariables);
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
     * @param bool      $removeAuxiliaryVariables
     *
     * @return array
     */
    protected function convertBlockViewToArray(BlockView $view, $removeAuxiliaryVariables = true)
    {
        $children = [];
        /** @var BlockView $childView */
        foreach ($view->children as $childView) {
            $children[] = $this->convertBlockViewToArray($childView, $removeAuxiliaryVariables);
        }

        $result = [
            'vars'     => $view->vars,
            'children' => $children
        ];

        if ($removeAuxiliaryVariables) {
            unset($result['vars']['translation_domain']);
            unset($result['vars']['label']);
            unset($result['vars']['unique_block_prefix']);
            unset($result['vars']['block_prefixes']);
            unset($result['vars']['cache_key']);
            unset($result['vars']['id']);
        }

        return $result;
    }
}
