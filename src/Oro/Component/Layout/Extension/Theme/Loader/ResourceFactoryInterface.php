<?php

namespace Oro\Component\Layout\Extension\Theme\Loader;

interface ResourceFactoryInterface
{
    /**
     * Creates resource object based on path and filename
     *
     * @param string $filename
     *
     * @return FileResource
     */
    public function create($filename);
}
