<?php

namespace Oro\Component\Layout;

/**
 * Provides a set of methods to manipulate the layout and apply the changes on demand
 */
interface DeferredLayoutModifierInterface extends LayoutModifierInterface
{
    /**
     * Applies all scheduled changes
     */
    public function applyChanges();
}
