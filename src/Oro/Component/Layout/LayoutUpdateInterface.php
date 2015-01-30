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
     * @param LayoutManipulatorInterface $layoutManipulator
     *
     * @return mixed
     */
    public function updateLayout(LayoutManipulatorInterface $layoutManipulator);
}
