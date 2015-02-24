<?php

namespace Oro\Bundle\LayoutBundle\Layout\Loader;

use Symfony\Component\Yaml\Yaml;

use Oro\Bundle\LayoutBundle\Layout\Generator\GeneratorData;

/**
 * Generates layout update object and instantiate it based on yml configuration file content.
 * Config should contain 'oro_layout' root node that should consist with array of actions in 'actions' node.
 * Extra keys are allowed and will be processed(or skipped) depends on generator.
 *
 * Example:
 *    oro_layout:
 *        actions:
 *            - @add:
 *              id:        test
 *              parent:    root
 *              blockType: block
 *
 * @see src/Oro/Bundle/LayoutBundle/Tests/Unit/Stubs/Updates/layout_update4.yml
 */
class YamlFileLoader extends AbstractLoader
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
        $data     = Yaml::parse($resource->getFilename());
        $data     = isset($data['oro_layout']) ? $data['oro_layout'] : [];

        return new GeneratorData($data);
    }
}
