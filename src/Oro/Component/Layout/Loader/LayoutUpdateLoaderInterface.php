<?php

namespace Oro\Component\Layout\Loader;

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

    /**
     * Get layout update filename patterns from all drivers
     *
     * @return array
     */
    public function getUpdateFileNamePatterns();
}
