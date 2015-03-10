<?php

namespace Oro\Bundle\LayoutBundle\Layout\Loader;

class ResourceFactory implements ResourceFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function create(array $path, $filename)
    {
        return new FileResource($filename);
    }
}
