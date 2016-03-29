<?php

namespace Oro\Bundle\ApiBundle\Config\Definition;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

use Oro\Bundle\ApiBundle\Config\SorterFieldConfig;
use Oro\Bundle\ApiBundle\Config\SortersConfig;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class SortersConfiguration extends AbstractConfigurationSection implements ConfigurationSectionInterface
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
        $sectionName = ConfigUtil::SORTERS;

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
            ->enumNode(SortersConfig::EXCLUSION_POLICY)
                ->values([SortersConfig::EXCLUSION_POLICY_ALL, SortersConfig::EXCLUSION_POLICY_NONE])
            ->end()
            ->arrayNode(SortersConfig::FIELDS)
                ->useAttributeAsKey('name')
                ->prototype('array')
                    ->children();
        $this->configureFieldNode($fieldNode, $configureCallbacks, $preProcessCallbacks, $postProcessCallbacks);
        $parentNode
            ->validate()
                ->always(
                    function ($value) use ($postProcessCallbacks, $sectionName) {
                        if (empty($value[SortersConfig::FIELDS])) {
                            unset($value[SortersConfig::FIELDS]);
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
        $sectionName = ConfigUtil::SORTERS . '.field';

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
            ->booleanNode(SorterFieldConfig::EXCLUDE)->end()
            ->scalarNode(SorterFieldConfig::PROPERTY_PATH)->cannotBeEmpty()->end();
        $parentNode
            ->validate()
                ->always(
                    function ($value) use ($postProcessCallbacks, $sectionName) {
                        return $this->callProcessConfigCallbacks($value, $postProcessCallbacks, $sectionName);
                    }
                );
    }
}
