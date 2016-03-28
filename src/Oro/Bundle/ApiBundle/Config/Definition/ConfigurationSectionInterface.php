<?php

namespace Oro\Bundle\ApiBundle\Config\Definition;

use Symfony\Component\Config\Definition\Builder\NodeBuilder;

interface ConfigurationSectionInterface
{
    /**
     * Builds the definition of a section configuration.
     *
     * @param NodeBuilder $node
     * @param array       $configureCallbacks
     * @param array       $preProcessCallbacks
     * @param array       $postProcessCallbacks
     */
    public function configure(
        NodeBuilder $node,
        array $configureCallbacks,
        array $preProcessCallbacks,
        array $postProcessCallbacks
    );

    /**
     * Checks if section can be added to the given configuration section
     *
     * @param string $section Configuration section, f.e. entities.entity, relations.entity etc
     *
     * @return bool
     */
    public function isApplicable($section);
}
