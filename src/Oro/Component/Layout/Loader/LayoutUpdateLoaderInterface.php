<?php

namespace Oro\Component\Layout\Extension\Theme\Loader;

use Oro\Component\Layout\LayoutUpdateInterface;

interface LayoutUpdateLoaderInterface
{
    /**
     * Loads the layout update instance from the given file.
     *
     * @param string $file
     *
     * @return LayoutUpdateInterface|null
     */
    public function load($file);
}
