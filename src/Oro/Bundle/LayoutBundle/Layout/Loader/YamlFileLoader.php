<?php

namespace Oro\Bundle\LayoutBundle\Layout\Loader;

use Symfony\Component\Yaml\Yaml;

use Oro\Bundle\LayoutBundle\Layout\Generator\ConfigLayoutUpdateGenerator;

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
    protected function getGenerator()
    {
        return new ConfigLayoutUpdateGenerator();
    }

    /**
     * {@inheritdoc}
     */
    protected function loadResourceGeneratorData(FileResource $resource)
    {
        return Yaml::parse($resource->getFilename());
    }
}
