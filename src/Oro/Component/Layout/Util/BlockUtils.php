<?php

namespace Oro\Component\Layout\Util;

use Oro\Component\Layout\BlockView;

class BlockUtils
{
    /**
     * Registers the plugin for the block type.
     * You can use this method to add the additional block prefix that allow you
     * to create an additional template for existing block type.
     *
     * IMPORTANT: This method should be called in finishView of your block type extension
     * because the 'block_prefixes' array is not filled in buildView yet.
     *
     * @param BlockView $view
     * @param string    $pluginName
     */
    public static function registerPlugin(BlockView $view, $pluginName)
    {
        array_splice(
            $view->vars['block_prefixes'],
            -1,
            1,
            [$pluginName, end($view->vars['block_prefixes'])]
        );
    }

    /**
     * This class cannot be instantiated.
     */
    private function __construct()
    {
    }
}
