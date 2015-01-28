<?php

namespace Oro\Component\Layout;

interface LayoutFactoryInterface
{
    /**
     * Creates a layout
     *
     * @param BlockView $view The view of the layout root item
     *
     * @return Layout
     */
    public function createLayout(BlockView $view);
}
