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

        // compare hierarchy
        $this->assertSame(
            $this->getViewHierarchy($expected),
            $this->getViewHierarchy($actualArray),
            'Failed asserting that two hierarchies are equal.'
        );
        // full compare
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
        if (!isset($view['vars']['id'])) {
            $view['vars']['id'] = '';
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
        }

        return $result;
    }

    /**
     * @param array $view
     *
     * @return array
     */
    protected function getViewHierarchy(array $view)
    {
        $id     = $view['vars']['id'];
        $result = [$id => null];
        if (!empty($view['children'])) {
            $result[$id] = [];
            $this->buildViewHierarchy($result[$id], $view);
        }

        return $result;
    }

    /**
     * @param array $result
     * @param array $view
     */
    protected function buildViewHierarchy(array &$result, array $view)
    {
        foreach ($view['children'] as $childView) {
            $childId          = $childView['vars']['id'];
            $result[$childId] = null;
            if (!empty($childView['children'])) {
                $result[$childId] = [];
                $this->buildViewHierarchy($result[$childId], $childView);
            }
        }
    }
}
