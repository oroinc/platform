<?php

namespace Oro\Component\Layout;

interface LayoutDataBuilderInterface extends RawLayoutAccessorInterface
{
    /**
     * Returns the built layout data
     *
     * @return LayoutData
     */
    public function getLayoutData();
}
