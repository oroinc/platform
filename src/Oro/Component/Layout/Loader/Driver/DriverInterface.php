<?php

namespace Oro\Component\Layout\Loader\Driver;

use Oro\Component\Layout\LayoutUpdateInterface;

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
}
