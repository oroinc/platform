<?php

namespace Oro\Component\Layout;

interface LayoutViewFactoryInterface
{
    /**
     * Creates the object represents the hierarchy of block view objects starting with the given root
     *
     * @param LayoutData  $layoutData
     * @param string|null $rootId
     *
     * @return BlockView
     */
    public function createView(LayoutData $layoutData, $rootId = null);
}
