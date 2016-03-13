<?php

namespace Oro\Bundle\ApiBundle\Config\Definition;

use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

class ActionsConfiguration extends AbstractConfigurationSection implements ConfigurationSectionInterface
{
    /**
     * {@inheritdoc}
     */
    public function configure(
        NodeBuilder $node,
        array $configureCallbacks,
        array $preProcessCallbacks,
        array $postProcessCallbacks
    ) {
        $sectionName = 'actions';
        /** @var ArrayNodeDefinition $parentNode */
        $parentNode = $node->end();
        $parentNode
            //->ignoreExtraKeys(false) @todo: uncomment after migration to Symfony 2.8+
            ->beforeNormalization()
            ->always(
                function ($value) use ($preProcessCallbacks, $sectionName) {
                    return $this->callProcessConfigCallbacks($value, $preProcessCallbacks, $sectionName);
                }
            );
        //$node->arrayNode()
        ////

//        $node->arrayNode('get_list')->end();
        $node->arrayNode('delete')->end();
        ////////////
        $this->callConfigureCallbacks($node, $configureCallbacks, $sectionName);
        $parentNode
            ->validate()
            ->always(
                function ($value) use ($postProcessCallbacks, $sectionName) {
                    return $this->callProcessConfigCallbacks($value, $postProcessCallbacks, $sectionName);
                }
            );
    }
}
