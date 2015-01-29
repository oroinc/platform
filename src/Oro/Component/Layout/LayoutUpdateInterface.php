<?php

namespace Oro\Component\Layout;

/**
 * Provides an interface for classes responsible to make changes in the layout
 */
interface LayoutUpdateInterface
{
    /**
     * Makes changes in the layout
     *
     * @param LayoutBuilderInterface $layoutBuilder
     *
     * @return mixed
     */
    public function updateLayout(LayoutBuilderInterface $layoutBuilder);
}
