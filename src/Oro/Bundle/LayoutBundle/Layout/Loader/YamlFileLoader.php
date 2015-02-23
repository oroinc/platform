<?php

namespace Oro\Bundle\LayoutBundle\Layout\Loader;

use Symfony\Component\Yaml\Yaml;

class YamlFileLoader extends AbstractGeneratorLoader
{
    /**
     * {@inheritdoc}
     */
    public function supports(FileResource $resource)
    {
        return is_string($resource->getFilename()) && 'yml' === pathinfo($resource->getFilename(), PATHINFO_EXTENSION);
    }

    /**
     * {@inheritdoc}
     */
    protected function loadResourceGeneratorData(FileResource $resource)
    {
        return Yaml::parse($resource->getFilename());
    }
}
