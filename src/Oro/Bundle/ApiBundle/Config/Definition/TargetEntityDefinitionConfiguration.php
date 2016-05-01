<?php

namespace Oro\Bundle\ApiBundle\Config\Definition;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;

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
    public function configure(NodeBuilder $node)
    {
        $sectionName = $this->sectionName;
        if (!empty($this->parentSectionName)) {
            $sectionName = $this->parentSectionName . '.' . $sectionName;
        }

        /** @var ArrayNodeDefinition $parentNode */
        $parentNode = $node->end();
        //$parentNode->ignoreExtraKeys(false); @todo: uncomment after migration to Symfony 2.8+
        $this->callConfigureCallbacks($node, $sectionName);
        $this->addPreProcessCallbacks($parentNode, $sectionName);
        $this->addPostProcessCallbacks(
            $parentNode,
            $sectionName,
            function ($value) {
                return $this->postProcessConfig($value);
            }
        );

        $this->configureEntityNode($node);
        $fieldNode = $node
            ->arrayNode(EntityDefinitionConfig::FIELDS)
                ->useAttributeAsKey('name')
                ->prototype('array')
                    ->children();
        $this->configureFieldNode($fieldNode);
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
        if (empty($config[EntityDefinitionConfig::FORM_TYPE])) {
            unset($config[EntityDefinitionConfig::FORM_TYPE]);
        }
        if (empty($config[EntityDefinitionConfig::FORM_OPTIONS])) {
            unset($config[EntityDefinitionConfig::FORM_OPTIONS]);
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
            ->variableNode(EntityDefinitionConfig::POST_SERIALIZE)->end()
            ->scalarNode(EntityDefinitionConfig::FORM_TYPE)->end()
            ->arrayNode(EntityDefinitionConfig::FORM_OPTIONS)
                ->useAttributeAsKey('name')
                ->performNoDeepMerging()
                ->prototype('variable')
                ->end()
            ->end();
    }

    /**
     * @param NodeBuilder $node
     */
    protected function configureFieldNode(NodeBuilder $node)
    {
        $sectionName = $this->sectionName . '.field';
        if (!empty($this->parentSectionName)) {
            $sectionName = $this->parentSectionName . '.' . $sectionName;
        }

        /** @var ArrayNodeDefinition $parentNode */
        $parentNode = $node->end();
        //$parentNode->ignoreExtraKeys(false); @todo: uncomment after migration to Symfony 2.8+
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
            ->booleanNode(EntityDefinitionFieldConfig::EXCLUDE)->end()
            ->scalarNode(EntityDefinitionFieldConfig::PROPERTY_PATH)->cannotBeEmpty()->end()
            ->scalarNode(EntityDefinitionFieldConfig::DATA_TYPE)->cannotBeEmpty()->end()
            ->booleanNode(EntityDefinitionFieldConfig::COLLAPSE)->end()
            ->variableNode(EntityDefinitionFieldConfig::DATA_TRANSFORMER)->end()
            ->scalarNode(EntityDefinitionFieldConfig::LABEL)->cannotBeEmpty()->end()
            ->scalarNode(EntityDefinitionFieldConfig::DESCRIPTION)->cannotBeEmpty()->end()
            ->scalarNode(EntityDefinitionFieldConfig::FORM_TYPE)->end()
            ->arrayNode(EntityDefinitionFieldConfig::FORM_OPTIONS)
                ->useAttributeAsKey('name')
                ->performNoDeepMerging()
                ->prototype('variable')
                ->end()
            ->end();
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
        if (empty($config[EntityDefinitionFieldConfig::FORM_TYPE])) {
            unset($config[EntityDefinitionFieldConfig::FORM_TYPE]);
        }
        if (empty($config[EntityDefinitionFieldConfig::FORM_OPTIONS])) {
            unset($config[EntityDefinitionFieldConfig::FORM_OPTIONS]);
        }

        return $config;
    }
}
