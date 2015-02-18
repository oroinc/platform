<?php

namespace Oro\Bundle\LayoutBundle\Layout\Loader;

use Oro\Component\Layout\LayoutUpdateInterface;

interface FileLoaderInterface
{
    /**
     * @param string $resource
     *
     * @return bool
     */
    public function supports($resource);

    /**
     * @param string $resource
     *
     * @return LayoutUpdateInterface
     */
    public function load($resource);
}
