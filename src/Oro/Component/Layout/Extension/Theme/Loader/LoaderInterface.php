<?php

namespace Oro\Component\Layout\Extension\Theme\Loader;

use Oro\Component\Layout\LayoutUpdateInterface;

interface LoaderInterface
{
    /**
     * Returns whether given file resource is supported by loader impl
     *
     * @param string $fileName
     *
     * @return bool
     */
    public function supports($fileName);

    /**
     * Load/generate layout update instance based on given file resource.
     *
     * @param string $fileName
     *
     * @return LayoutUpdateInterface
     */
    public function load($fileName);
}
