<?php

namespace Oro\Component\Layout\Extension\Theme\Loader;

use Oro\Component\Layout\Extension\Theme\Loader\Driver\DriverInterface;

class LayoutUpdateLoader implements LayoutUpdateLoaderInterface
{
    /** @var DriverInterface[] */
    protected $drivers = [];

    /**
     * @param string          $fileExt
     * @param DriverInterface $driver
     */
    public function addDriver($fileExt, DriverInterface $driver)
    {
        $this->drivers[$fileExt] = $driver;
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
}
