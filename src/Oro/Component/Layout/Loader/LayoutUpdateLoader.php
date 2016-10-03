<?php

namespace Oro\Component\Layout\Loader;

use Oro\Component\Layout\Loader\Driver\DriverInterface;

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

    /**
     * {@inheritdoc}
     */
    public function load($file)
    {
        $fileExt = pathinfo($file, PATHINFO_EXTENSION);
        if (!isset($this->drivers[$fileExt])) {
            return null;
        }

        $driver = $this->drivers[$fileExt];

        return $driver->load($file);
    }

    /**
     * {@inheritdoc}
     */
    public function getUpdateFileNamePatterns()
    {
        return $this->updateFileNamePatterns;
    }
}
