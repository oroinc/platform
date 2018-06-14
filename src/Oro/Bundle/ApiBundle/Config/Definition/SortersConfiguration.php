<?php

namespace Oro\Bundle\ApiBundle\Config\Definition;

use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * The configuration of elements in "sorters" section.
 */
class SortersConfiguration extends AbstractConfigurationSection
{
    /**
     * {@inheritdoc}
     */
    public function configure(NodeBuilder $node): void
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
                if (empty($value[ConfigUtil::FIELDS])) {
                    unset($value[ConfigUtil::FIELDS]);
                }

                return $value;
            }
        );

        $fieldNode = $node
            ->enumNode(ConfigUtil::EXCLUSION_POLICY)
                ->values([ConfigUtil::EXCLUSION_POLICY_ALL, ConfigUtil::EXCLUSION_POLICY_NONE])
            ->end()
            ->arrayNode(ConfigUtil::FIELDS)
                ->useAttributeAsKey('name')
                ->normalizeKeys(false)
                ->prototype('array')
                    ->children();
        $this->configureFieldNode($fieldNode);
    }

    /**
     * @param NodeBuilder $node
     */
    protected function configureFieldNode(NodeBuilder $node): void
    {
        $sectionName = 'sorters.field';

        /** @var ArrayNodeDefinition $parentNode */
        $parentNode = $node->end();
        $this->callConfigureCallbacks($node, $sectionName);
        $this->addPreProcessCallbacks($parentNode, $sectionName);
        $this->addPostProcessCallbacks($parentNode, $sectionName);

        $node
            ->booleanNode(ConfigUtil::EXCLUDE)->end()
            ->scalarNode(ConfigUtil::PROPERTY_PATH)->cannotBeEmpty()->end();
    }
}
