<?php

namespace Oro\Bundle\ApiBundle\Config\Definition;

use Oro\Bundle\ApiBundle\Config\SorterFieldConfig;
use Oro\Bundle\ApiBundle\Config\SortersConfig;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

class SortersConfiguration extends AbstractConfigurationSection
{
    /**
     * {@inheritdoc}
     */
    public function configure(NodeBuilder $node)
    {
        $sectionName = 'sorters';

        /** @var ArrayNodeDefinition $parentNode */
        $parentNode = $node->end();
        $this->callConfigureCallbacks($node, $sectionName);
        $this->addPreProcessCallbacks($parentNode, $sectionName);
        $this->addPostProcessCallbacks(
            $parentNode,
            $sectionName,
            function ($value) {
                if (empty($value[SortersConfig::FIELDS])) {
                    unset($value[SortersConfig::FIELDS]);
                }

                return $value;
            }
        );

        $fieldNode = $node
            ->enumNode(SortersConfig::EXCLUSION_POLICY)
                ->values([SortersConfig::EXCLUSION_POLICY_ALL, SortersConfig::EXCLUSION_POLICY_NONE])
            ->end()
            ->arrayNode(SortersConfig::FIELDS)
                ->useAttributeAsKey('name')
                ->normalizeKeys(false)
                ->prototype('array')
                    ->children();
        $this->configureFieldNode($fieldNode);
    }

    /**
     * @param NodeBuilder $node
     */
    protected function configureFieldNode(NodeBuilder $node)
    {
        $sectionName = 'sorters.field';

        /** @var ArrayNodeDefinition $parentNode */
        $parentNode = $node->end();
        $this->callConfigureCallbacks($node, $sectionName);
        $this->addPreProcessCallbacks($parentNode, $sectionName);
        $this->addPostProcessCallbacks($parentNode, $sectionName);

        $node
            ->booleanNode(SorterFieldConfig::EXCLUDE)->end()
            ->scalarNode(SorterFieldConfig::PROPERTY_PATH)->cannotBeEmpty()->end();
    }
}
