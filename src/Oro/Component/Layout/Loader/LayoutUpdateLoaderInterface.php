<?php

namespace Oro\Component\Layout\Loader;

use Oro\Component\Layout\LayoutUpdateInterface;

/**
 * Defines the contract for loading layout updates from files.
 *
 * Implementations of this interface load layout update instances from files and provide
 * filename patterns that identify layout update files for all supported formats.
 */
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
