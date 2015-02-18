<?php

namespace Oro\Bundle\LayoutBundle\Layout\Loader;

class YamlFileLoader implements FileLoaderInterface
{
    /**
     * {@inheritdoc}
     */
    public function load($resource)
    {
        // TODO: Implement load() method.
    }

    /**
     * {@inheritdoc}
     */
    public function supports($resource)
    {
        return is_string($resource) && 'yml' === pathinfo($resource, PATHINFO_EXTENSION);
    }
}
