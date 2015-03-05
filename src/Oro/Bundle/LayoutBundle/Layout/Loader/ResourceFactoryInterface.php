<?php

namespace Oro\Bundle\LayoutBundle\Layout\Loader;

interface ResourceFactoryInterface
{
    /**
     * Creates resource object based on path and filename
     *
     * @param array $path      Relative to 'layout' directory path
     * @param string $filename
     *
     * @return FileResource
     */
    public function create(array $path, $filename);
}
