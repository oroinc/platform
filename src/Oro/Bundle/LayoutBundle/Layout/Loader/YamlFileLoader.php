<?php

namespace Oro\Bundle\LayoutBundle\Layout\Loader;

use Oro\Bundle\LayoutBundle\Layout\Generator\GeneratorData;
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
        $filename = $resource->getFilename();
        $data     = Yaml::parse($filename);
        $data     = isset($data['oro_layout']) ? $data['oro_layout'] : [];

        return new GeneratorData($data, $filename);
    }
}
