<?php

namespace Oro\Bundle\LayoutBundle\Layout\Loader;

use Oro\Component\Layout\LayoutUpdateInterface;

interface LoaderInterface
{
    /**
     * @param FileResource $resource
     *
     * @return bool
     */
    public function supports(FileResource $resource);

    /**
     * @param FileResource $resource
     *
     * @return LayoutUpdateInterface
     */
    public function load(FileResource $resource);
}
