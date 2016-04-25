<?php

namespace Oro\Bundle\ApiBundle\Config\Definition;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

use Oro\Bundle\ApiBundle\Config\FilterFieldConfig;
use Oro\Bundle\ApiBundle\Config\FiltersConfig;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class FiltersConfiguration extends AbstractConfigurationSection implements ConfigurationSectionInterface
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
        $sectionName = ConfigUtil::FILTERS;

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
        $fieldNode = $node
            ->enumNode(FiltersConfig::EXCLUSION_POLICY)
                ->values([FiltersConfig::EXCLUSION_POLICY_ALL, FiltersConfig::EXCLUSION_POLICY_NONE])
            ->end()
            ->arrayNode(FiltersConfig::FIELDS)
                ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->children();
        $this->configureFieldNode($fieldNode, $configureCallbacks, $preProcessCallbacks, $postProcessCallbacks);
        $parentNode
            ->validate()
                ->always(
                    function ($value) use ($postProcessCallbacks, $sectionName) {
                        if (empty($value[FiltersConfig::FIELDS])) {
                            unset($value[FiltersConfig::FIELDS]);
                        }
                        return $this->callProcessConfigCallbacks($value, $postProcessCallbacks, $sectionName);
                    }
                );
    }

    /**
     * @param NodeBuilder $node
     * @param array       $configureCallbacks
     * @param array       $preProcessCallbacks
     * @param array       $postProcessCallbacks
     */
    protected function configureFieldNode(
        NodeBuilder $node,
        array $configureCallbacks,
        array $preProcessCallbacks,
        array $postProcessCallbacks
    ) {
        $sectionName = ConfigUtil::FILTERS . '.field';

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
        $node
            ->booleanNode(FilterFieldConfig::EXCLUDE)->end()
            ->scalarNode(FilterFieldConfig::PROPERTY_PATH)->cannotBeEmpty()->end()
            ->scalarNode(FilterFieldConfig::DATA_TYPE)->cannotBeEmpty()->end()
            ->booleanNode(FilterFieldConfig::ALLOW_ARRAY)->end()
            ->scalarNode(FilterFieldConfig::DESCRIPTION)->cannotBeEmpty()->end();
        $parentNode
            ->validate()
                ->always(
                    function ($value) use ($postProcessCallbacks, $sectionName) {
                        return $this->callProcessConfigCallbacks($value, $postProcessCallbacks, $sectionName);
                    }
                );
    }
}
