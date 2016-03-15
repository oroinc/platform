<?php

namespace Oro\Bundle\ApiBundle\Config\Definition;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;

class TargetEntityDefinitionConfiguration extends AbstractConfigurationSection implements
    ConfigurationSectionInterface
{
    /** @var string */
    protected $parentSectionName;

    /** @var string */
    protected $sectionName;

    /**
     * @param string $sectionName
     */
    public function __construct($sectionName = 'entity')
    {
        $this->sectionName = $sectionName;
    }

    /**
     * Gets the name of the section.
     *
     * @return string
     */
    public function getSectionName()
    {
        return $this->sectionName;
    }

    /**
     * Gets the name of the parent section.
     *
     * @return string|null
     */
    public function getParentSectionName()
    {
        return $this->parentSectionName;
    }

    /**
     * Sets the name of the parent section.
     *
     * @param string $sectionName
     */
    public function setParentSectionName($sectionName)
    {
        $this->parentSectionName = $sectionName;
    }

    /**
     * {@inheritdoc}
     */
    public function configure(
        NodeBuilder $node,
        array $configureCallbacks,
        array $preProcessCallbacks,
        array $postProcessCallbacks
    ) {
        $sectionName = $this->sectionName;
        if (!empty($this->parentSectionName)) {
            $sectionName = $this->parentSectionName . '.' . $sectionName;
        }

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
        $this->configureEntityNode($node);
        $fieldNode = $node
            ->arrayNode(EntityDefinitionConfig::FIELDS)
                ->useAttributeAsKey('name')
                ->prototype('array')
                    ->children();
        $this->configureFieldNode($fieldNode, $configureCallbacks, $preProcessCallbacks, $postProcessCallbacks);
        $parentNode
            ->validate()
                ->always(
                    function ($value) use ($postProcessCallbacks, $sectionName) {
                        return $this->callProcessConfigCallbacks(
                            $this->postProcessConfig($value),
                            $postProcessCallbacks,
                            $sectionName
                        );
                    }
                );
    }

    /**
     * @param array $config
     *
     * @return array
     */
    protected function postProcessConfig(array $config)
    {
        if (empty($config[EntityDefinitionConfig::ORDER_BY])) {
            unset($config[EntityDefinitionConfig::ORDER_BY]);
        }
        if (empty($config[EntityDefinitionConfig::HINTS])) {
            unset($config[EntityDefinitionConfig::HINTS]);
        }
        if (empty($config[EntityDefinitionConfig::POST_SERIALIZE])) {
            unset($config[EntityDefinitionConfig::POST_SERIALIZE]);
        }
        if (empty($config[EntityDefinitionConfig::FIELDS])) {
            unset($config[EntityDefinitionConfig::FIELDS]);
        }

        return $config;
    }

    /**
     * @param NodeBuilder $node
     */
    public function configureEntityNode(NodeBuilder $node)
    {
        $node
            ->enumNode(EntityDefinitionConfig::EXCLUSION_POLICY)
                ->values(
                    [EntityDefinitionConfig::EXCLUSION_POLICY_ALL, EntityDefinitionConfig::EXCLUSION_POLICY_NONE]
                )
            ->end()
            ->booleanNode(EntityDefinitionConfig::DISABLE_PARTIAL_LOAD)->end()
            ->arrayNode(EntityDefinitionConfig::ORDER_BY)
                ->performNoDeepMerging()
                ->useAttributeAsKey('name')
                ->prototype('enum')
                    ->values(['ASC', 'DESC'])
                ->end()
            ->end()
            ->integerNode(EntityDefinitionConfig::MAX_RESULTS)
                ->min(-1)
            ->end()
            ->arrayNode(EntityDefinitionConfig::HINTS)
                ->prototype('array')
                    ->beforeNormalization()
                        ->ifString()
                        ->then(
                            function ($v) {
                                return ['name' => $v];
                            }
                        )
                    ->end()
                    ->children()
                        ->scalarNode('name')->cannotBeEmpty()->end()
                        ->variableNode('value')->end()
                    ->end()
                ->end()
            ->end()
            ->variableNode(EntityDefinitionConfig::POST_SERIALIZE)->end();
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
        $sectionName = $this->sectionName . '.' . EntityDefinitionConfig::FIELDS;
        if (!empty($this->parentSectionName)) {
            $sectionName = $this->parentSectionName . '.' . $sectionName;
        }

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
            ->booleanNode(EntityDefinitionFieldConfig::EXCLUDE)->end()
            ->scalarNode(EntityDefinitionFieldConfig::PROPERTY_PATH)->cannotBeEmpty()->end()
            ->booleanNode(EntityDefinitionFieldConfig::COLLAPSE)->end()
            ->variableNode(EntityDefinitionFieldConfig::DATA_TRANSFORMER)->end()
            ->scalarNode(EntityDefinitionFieldConfig::LABEL)->cannotBeEmpty()->end()
            ->scalarNode(EntityDefinitionFieldConfig::DESCRIPTION)->cannotBeEmpty()->end();
        $parentNode
            ->validate()
                ->always(
                    function ($value) use ($postProcessCallbacks, $sectionName) {
                        return $this->callProcessConfigCallbacks(
                            $this->postProcessFieldConfig($value),
                            $postProcessCallbacks,
                            $sectionName
                        );
                    }
                );
    }

    /**
     * @param array $config
     *
     * @return array
     */
    protected function postProcessFieldConfig(array $config)
    {
        if (empty($config[EntityDefinitionFieldConfig::DATA_TRANSFORMER])) {
            unset($config[EntityDefinitionFieldConfig::DATA_TRANSFORMER]);
        }

        return $config;
    }
}
