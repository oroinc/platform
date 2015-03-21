<?php

namespace Oro\Component\Layout\Extension\Theme\Loader;

use Symfony\Component\Yaml\Yaml;

use Oro\Component\Layout\Extension\Theme\Generator\GeneratorData;

/**
 * Generates layout update object and instantiate it based on yml configuration file content.
 * Config should contain "layout" root node that should consist with array of actions in "actions" node.
 * Extra keys are allowed and will be processed(or skipped) depends on generator.
 *
 * Example:
 *    layout:
 *        actions:
 *            - @add:
 *              id:        test
 *              parent:    root
 *              blockType: block
 *
 * @see src/Oro/Component/Layout/Tests/Unit/Extension/Theme/Stubs/Updates/layout_update4.yml
 */
class YamlFileLoader extends AbstractLoader
{
    /**
     * {@inheritdoc}
     */
    public function supports(FileResource $resource)
    {
        return 'yml' === pathinfo($resource->getFilename(), PATHINFO_EXTENSION);
    }

    /**
     * {@inheritdoc}
     */
    protected function loadResourceGeneratorData(FileResource $resource)
    {
        $data = Yaml::parse($resource->getFilename());
        $data = isset($data['layout']) ? $data['layout'] : [];

        return new GeneratorData($data);
    }

    /**
     * {@inheritdoc}
     */
    protected function dumpSource($source)
    {
        return Yaml::dump($source);
    }
}
