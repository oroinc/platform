<?php

namespace Oro\Bundle\ApiBundle\Config\Definition;

use Oro\Bundle\ApiBundle\Filter\FilterOperatorRegistry;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * The configuration of elements in "filters" section.
 */
class FiltersConfiguration extends AbstractConfigurationSection
{
    /** @var FilterOperatorRegistry */
    private $filterOperatorRegistry;

    /**
     * @param FilterOperatorRegistry $filterOperatorRegistry
     */
    public function __construct(FilterOperatorRegistry $filterOperatorRegistry)
    {
        $this->filterOperatorRegistry = $filterOperatorRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function configure(NodeBuilder $node): void
    {
        $sectionName = 'filters';

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
        $sectionName = 'filters.field';

        /** @var ArrayNodeDefinition $parentNode */
        $parentNode = $node->end();
        $this->callConfigureCallbacks($node, $sectionName);
        $this->addPreProcessCallbacks($parentNode, $sectionName);
        $this->addPostProcessCallbacks(
            $parentNode,
            $sectionName,
            function ($value) {
                return $this->postProcessFieldConfig($value);
            }
        );

        $node
            ->booleanNode(ConfigUtil::EXCLUDE)->end()
            ->scalarNode(ConfigUtil::DESCRIPTION)->cannotBeEmpty()->end()
            ->scalarNode(ConfigUtil::PROPERTY_PATH)->cannotBeEmpty()->end()
            ->scalarNode(ConfigUtil::FILTER_TYPE)->cannotBeEmpty()->end()
            ->arrayNode(ConfigUtil::FILTER_OPTIONS)
                ->useAttributeAsKey('name')
                ->performNoDeepMerging()
                ->prototype('variable')->end()
            ->end()
            ->arrayNode(ConfigUtil::FILTER_OPERATORS)
                ->validate()
                    ->always(function ($value) {
                        if (\is_array($value) && !empty($value)) {
                            $operators = [];
                            foreach ($value as $val) {
                                $operators[] = $this->filterOperatorRegistry->resolveOperator($val);
                            }
                            $value = $operators;
                        }

                        return $value;
                    })
                ->end()
                ->prototype('scalar')->end()
            ->end()
            ->scalarNode(ConfigUtil::DATA_TYPE)->cannotBeEmpty()->end()
            ->booleanNode(ConfigUtil::COLLECTION)->end()
            ->booleanNode(ConfigUtil::ALLOW_ARRAY)->end()
            ->booleanNode(ConfigUtil::ALLOW_RANGE)->end();
    }

    /**
     * @param array $config
     *
     * @return array
     */
    protected function postProcessFieldConfig(array $config): array
    {
        if (empty($config[ConfigUtil::FILTER_OPTIONS])) {
            unset($config[ConfigUtil::FILTER_OPTIONS]);
        }
        if (empty($config[ConfigUtil::FILTER_OPERATORS])) {
            unset($config[ConfigUtil::FILTER_OPERATORS]);
        }

        return $config;
    }
}
