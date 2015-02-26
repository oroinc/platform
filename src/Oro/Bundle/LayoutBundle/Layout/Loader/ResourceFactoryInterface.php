<?php

namespace Oro\Bundle\LayoutBundle\Layout\Loader;

interface ResourceFactoryInterface
{
    /**
     * Creates resource object based on path and filename
     *
     * @param string $path
     * @param string $filename
     *
     * @return FileResource
     */
    public function create($path, $filename);
}
