<?php

namespace Oro\Component\Layout;

interface LayoutBuilderInterface extends LayoutModifierInterface
{
    /**
     * Creates the layout
     *
     * @param string|null $rootId The id or alias of the root item
     *
     * @return Layout
     */
    public function getLayout($rootId = null);
}
