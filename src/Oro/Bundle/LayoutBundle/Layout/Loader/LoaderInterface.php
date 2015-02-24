<?php

namespace Oro\Bundle\LayoutBundle\Layout\Loader;

use Oro\Component\Layout\LayoutUpdateInterface;

interface LoaderInterface
{
    /**
     * Returns whether given file resource is supported by loader impl
     *
     * @param FileResource $resource
     *
     * @return bool
     */
    public function supports(FileResource $resource);

    /**
     * Load/generate layout update instance based on given file resource.
     *
     * @param FileResource $resource
     *
     * @return LayoutUpdateInterface
     */
    public function load(FileResource $resource);
}
