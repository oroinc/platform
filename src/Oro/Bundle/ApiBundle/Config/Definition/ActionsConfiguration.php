<?php

namespace Oro\Bundle\ApiBundle\Config\Definition;

use Symfony\Component\Config\Definition\Builder\NodeBuilder;

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
        $this->callConfigureCallbacks($node, $configureCallbacks, $sectionName);

        $parentNode->useAttributeAsKey('name')->prototype('array')->children()
                ->booleanNode('enabled')->cannotBeEmpty()->defaultTrue()->end()
                ->scalarNode('delete_handler')->cannotBeEmpty()->end()
                ->scalarNode('acl_resource')->cannotBeEmpty()->end();

        $parentNode
            ->validate()
            ->always(
                function ($value) use ($postProcessCallbacks, $sectionName) {
                    if (empty($value[$sectionName])) {
                        unset($value[$sectionName]);
                    }
                    return $this->callProcessConfigCallbacks($value, $postProcessCallbacks, $sectionName);
                }
            );
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable($section)
    {
        return $section === 'entities.entity';
    }
}
