<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\Block\Type\Options;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\BlockViewCollection;

class LayoutTestCase extends \PHPUnit\Framework\TestCase
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
        $views = new BlockViewCollection($views);

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
     */
    protected function completeView(array &$view, $vars)
    {
        $this->setDefaultValue($view['vars'], []);
        $this->setDefaultValue($view['vars']['id'], '');
        $this->setDefaultValue($view['vars']['attr'], []);
        $this->setDefaultValue($view['vars']['class_prefix'], null);
        $this->setDefaultValue($view['vars']['visible'], true);
        $this->setDefaultValue($view['children'], []);

        if ($view['vars'] instanceof Options) {
            $merged = array_merge($vars, $view['vars']->toArray());
            $view['vars'] = new Options($merged);
        } else {
            $view['vars'] = array_merge($vars, $view['vars']);
        }

        foreach ($view['children'] as &$child) {
            $this->completeView($child, $vars);
        }
    }

    /**
     * @param mixed $value
     * @param mixed $defaultValue
     */
    protected function setDefaultValue(&$value, $defaultValue)
    {
        if (!isset($value)) {
            $value = $defaultValue;
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
            unset(
                $result['vars']['translation_domain'],
                $result['vars']['label'],
                $result['vars']['block_type'],
                $result['vars']['block_type_widget_id'],
                $result['vars']['unique_block_prefix'],
                $result['vars']['block_prefixes'],
                $result['vars']['cache_key']
            );
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
            $viewsCollection = new BlockViewCollection($views);
            foreach ($views as $view) {
                $view->blocks = $viewsCollection;
            }
        }

        return $views;
    }
}
