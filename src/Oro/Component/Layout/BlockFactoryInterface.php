<?php

namespace Oro\Component\Layout;

interface LayoutViewFactoryInterface
{
    /**
     * Creates the object represents the hierarchy of block view objects starting with the given root
     *
     * @param RawLayout        $rawLayout
     * @param ContextInterface $context
     * @param string|null      $rootId
     *
     * @return BlockView
     */
    public function createView(RawLayout $rawLayout, ContextInterface $context, $rootId = null);
}
