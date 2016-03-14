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
     * Checks if section can be added to the given section
     *
     * @param string $section
     *
     * @return bool
     */
    public function isApplicable($section);
}
