<?php

namespace Oro\Component\Layout;

interface RawLayoutBuilderInterface extends RawLayoutAccessorInterface
{
    /**
     * Returns the built layout data
     *
     * @return RawLayout
     */
    public function getRawLayout();
}
