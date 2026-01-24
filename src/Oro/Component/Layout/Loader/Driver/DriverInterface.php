<?php

namespace Oro\Component\Layout\Loader\Driver;

use Oro\Component\Layout\LayoutUpdateInterface;

/**
 * Defines the contract for loading layout updates from files.
 *
 * Drivers are responsible for loading and generating layout update instances from files of specific formats
 * (e.g., YAML, PHP), and providing the filename pattern that identifies layout update files for their format.
 */
interface DriverInterface
{
    /**
     * Load/generate layout update instance based on given file resource.
     *
     * @param string $file
     *
     * @return LayoutUpdateInterface
     */
    public function load($file);

    /**
     * Return pattern of layout update filename
     *
     * @param string $fileExtension
     *
     * @return string
     */
    public function getUpdateFilenamePattern($fileExtension);
}
