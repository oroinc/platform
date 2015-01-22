<?php

namespace Oro\Component\Layout;

interface ScheduledLayoutModifierInterface extends LayoutModifierInterface
{
    /**
     * Applies all scheduled actions
     */
    public function applyChanges();
}
