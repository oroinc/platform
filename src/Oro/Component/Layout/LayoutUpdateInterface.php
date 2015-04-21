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
     * @param LayoutManipulatorInterface $layoutManipulator The layout manipulator
     * @param LayoutItemInterface        $item              The layout item for which the update is executed
     */
    public function updateLayout(LayoutManipulatorInterface $layoutManipulator, LayoutItemInterface $item);
}
