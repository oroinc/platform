<?php

namespace Oro\Component\Layout\Extension\Theme\Loader;

class ResourceFactory implements ResourceFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function create($filename)
    {
        return new FileResource($filename);
    }
}
