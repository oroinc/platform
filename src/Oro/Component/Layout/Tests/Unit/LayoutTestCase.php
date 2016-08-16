<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\ArrayCollection;
use Oro\Component\Layout\BlockView;

class LayoutTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @param array     $expected
     * @param BlockView $actual
     * @param bool      $ignoreAuxiliaryVariables
     */
    protected function assertBlockView(array $expected, BlockView $actual, $ignoreAuxiliaryVariables = true)
    {
        $views = [];
        $collectViews = function (BlockView $view) use (&$collectViews, &$views) {
            $views[$view->vars['id']] = $view;
            array_walk($view->children, $collectViews);
        };
        $collectViews($actual);
        $views = new ArrayCollection($views);

        $this->completeView($expected, ['blocks' => $views]);
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
     * @param array $vars
     *
     * @return array
     */
    protected function completeView(array &$view, $vars)
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
        if (!isset($view['children'])) {
            $view['children'] = [];
        }
        if (!isset($view['vars']['class_prefix'])) {
            $view['vars']['class_prefix'] = null;
        }

        $view['vars'] = array_merge($vars, $view['vars']);

        foreach ($view['children'] as &$child) {
            $this->completeView($child, $vars);
        }
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

        unset($result['vars']['block']);

        if ($removeAuxiliaryVariables) {
            unset($result['vars']['translation_domain']);
            unset($result['vars']['label']);
            unset($result['vars']['block_type']);
            unset($result['vars']['block_type_widget_id']);
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

    /**
     * @param array $addViews
     * @param bool $root
     * @return array
     */
    protected function setLayoutBlocks($addViews, $root = true)
    {
        $views = $addViews;
        foreach ($views as $view) {
            $views = array_merge($views, $this->setLayoutBlocks($view->children, false));
        }

        if ($root) {
            $viewsCollection = new ArrayCollection($views);
            foreach ($views as $view) {
                $view->blocks = $viewsCollection;
            }
        }

        return $views;
    }
}
