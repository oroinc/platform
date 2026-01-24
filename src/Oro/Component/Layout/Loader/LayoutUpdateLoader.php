<?php

namespace Oro\Component\Layout\Loader;

use Oro\Component\Layout\Loader\Driver\DriverInterface;

/**
 * Loads layout updates from files using format-specific drivers.
 *
 * This loader maintains a registry of drivers indexed by file extension and delegates loading
 * to the appropriate driver based on the file extension of the layout update file.
 */
class LayoutUpdateLoader implements LayoutUpdateLoaderInterface
{
    /** @var DriverInterface[] */
    protected $drivers = [];

    /** @var array */
    protected $updateFileNamePatterns = [];

    /**
     * @param string          $fileExt
     * @param DriverInterface $driver
     */
    public function addDriver($fileExt, DriverInterface $driver)
    {
        $this->drivers[$fileExt] = $driver;
        $this->updateFileNamePatterns[] = $driver->getUpdateFilenamePattern($fileExt);
    }

    #[\Override]
    public function load($file)
    {
        $fileExt = pathinfo($file, PATHINFO_EXTENSION);
        if (!isset($this->drivers[$fileExt])) {
            return null;
        }

        $driver = $this->drivers[$fileExt];

        return $driver->load($file);
    }

    #[\Override]
    public function getUpdateFileNamePatterns()
    {
        return $this->updateFileNamePatterns;
    }
}
