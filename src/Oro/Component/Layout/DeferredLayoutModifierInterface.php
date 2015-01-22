<?php

namespace Oro\Component\Layout;

interface DeferredLayoutModifierInterface extends LayoutModifierInterface
{
    /**
     * Applies all scheduled changes
     */
    public function applyChanges();
}
