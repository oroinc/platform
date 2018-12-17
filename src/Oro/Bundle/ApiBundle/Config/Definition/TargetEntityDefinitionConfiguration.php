<?php

namespace Oro\Bundle\ApiBundle\Config\Definition;

use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * The base class for "entities" and "relations" configuration section builders.
 */
class TargetEntityDefinitionConfiguration extends AbstractConfigurationSection
{
    /** @var string */
    protected $parentSectionName;

    /** @var string */
    protected $sectionName;

    /**
     * @param string $sectionName
     */
    public function __construct(string $sectionName = 'entity')
    {
        $this->sectionName = $sectionName;
    }

    /**
     * Gets the name of the section.
     *
     * @return string
     */
    public function getSectionName(): string
    {
        return $this->sectionName;
    }

    /**
     * Gets the name of the parent section.
     *
     * @return string|null
     */
    public function getParentSectionName(): ?string
    {
        return $this->parentSectionName;
    }

    /**
     * Sets the name of the parent section.
     *
     * @param string $sectionName
     */
    public function setParentSectionName(string $sectionName): void
    {
        $this->parentSectionName = $sectionName;
    }

    /**
     * {@inheritdoc}
     */
    public function configure(NodeBuilder $node): void
    {
        $sectionName = $this->sectionName;
        if (!empty($this->parentSectionName)) {
            $sectionName = $this->parentSectionName . '.' . $sectionName;
        }

        /** @var ArrayNodeDefinition $parentNode */
        $parentNode = $node->end();
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
            ->arrayNode(ConfigUtil::FIELDS)
                ->useAttributeAsKey('name')
                ->normalizeKeys(false)
                ->prototype('array')
                    ->children();
        $this->configureFieldNode($fieldNode);
    }

    /**
     * @param array $config
     *
     * @return array
     */
    protected function postProcessConfig(array $config): array
    {
        if (empty($config[ConfigUtil::ORDER_BY])) {
            unset($config[ConfigUtil::ORDER_BY]);
        }
        if (empty($config[ConfigUtil::HINTS])) {
            unset($config[ConfigUtil::HINTS]);
        }
        if (empty($config[ConfigUtil::FORM_TYPE])) {
            unset($config[ConfigUtil::FORM_TYPE]);
        }
        if (empty($config[ConfigUtil::FORM_OPTIONS])) {
            unset($config[ConfigUtil::FORM_OPTIONS]);
        }
        if (empty($config[ConfigUtil::FORM_EVENT_SUBSCRIBER])) {
            unset($config[ConfigUtil::FORM_EVENT_SUBSCRIBER]);
        }
        if (empty($config[ConfigUtil::FIELDS])) {
            unset($config[ConfigUtil::FIELDS]);
        }

        return $config;
    }

    /**
     * @param NodeBuilder $node
     */
    public function configureEntityNode(NodeBuilder $node): void
    {
        $node
            ->enumNode(ConfigUtil::EXCLUSION_POLICY)
                ->values([
                    ConfigUtil::EXCLUSION_POLICY_ALL,
                    ConfigUtil::EXCLUSION_POLICY_CUSTOM_FIELDS,
                    ConfigUtil::EXCLUSION_POLICY_NONE
                ])
            ->end()
            ->integerNode(ConfigUtil::MAX_RESULTS)->min(-1)->end()
            ->arrayNode(ConfigUtil::ORDER_BY)
                ->performNoDeepMerging()
                ->useAttributeAsKey('name')
                ->prototype('enum')->values(['ASC', 'DESC'])->end()
            ->end()
            ->arrayNode(ConfigUtil::HINTS)
                ->prototype('array')
                    ->beforeNormalization()
                        ->ifString()
                        ->then(function ($v) {
                            return ['name' => $v];
                        })
                    ->end()
                    ->children()
                        ->scalarNode('name')->cannotBeEmpty()->end()
                        ->variableNode('value')->end()
                    ->end()
                ->end()
            ->end()
            ->scalarNode(ConfigUtil::FORM_TYPE)->end()
            ->arrayNode(ConfigUtil::FORM_OPTIONS)
                ->useAttributeAsKey('name')
                ->performNoDeepMerging()
                ->prototype('variable')->end()
            ->end()
            ->variableNode(ConfigUtil::FORM_EVENT_SUBSCRIBER)
                ->validate()
                    ->always(function ($v) {
                        if (\is_string($v)) {
                            return [$v];
                        }
                        if (\is_array($v)) {
                            return $v;
                        }
                        throw new \InvalidArgumentException(
                            'The value must be a string or an array.'
                        );
                    })
                ->end()
            ->end();
    }

    /**
     * @param NodeBuilder $node
     */
    protected function configureFieldNode(NodeBuilder $node): void
    {
        $sectionName = $this->sectionName . '.field';
        if (!empty($this->parentSectionName)) {
            $sectionName = $this->parentSectionName . '.' . $sectionName;
        }

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
            ->scalarNode(ConfigUtil::DATA_TYPE)->cannotBeEmpty()->end()
            ->scalarNode(ConfigUtil::TARGET_CLASS)->end()
            ->enumNode(ConfigUtil::TARGET_TYPE)
                ->values(['to-many', 'to-one', 'collection'])
            ->end()
            ->booleanNode(ConfigUtil::COLLAPSE)->end()
            ->scalarNode(ConfigUtil::FORM_TYPE)->end()
            ->arrayNode(ConfigUtil::FORM_OPTIONS)
                ->useAttributeAsKey('name')
                ->performNoDeepMerging()
                ->prototype('variable')->end()
            ->end()
            ->arrayNode(ConfigUtil::DEPENDS_ON)
                ->prototype('scalar')->end()
            ->end();
    }

    /**
     * @param array $config
     *
     * @return array
     */
    protected function postProcessFieldConfig(array $config): array
    {
        if (empty($config[ConfigUtil::FORM_TYPE])) {
            unset($config[ConfigUtil::FORM_TYPE]);
        }
        if (empty($config[ConfigUtil::FORM_OPTIONS])) {
            unset($config[ConfigUtil::FORM_OPTIONS]);
        }
        if (!empty($config[ConfigUtil::TARGET_TYPE])) {
            if ('collection' === $config[ConfigUtil::TARGET_TYPE]) {
                $config[ConfigUtil::TARGET_TYPE] = 'to-many';
            }
        } elseif (!empty($config[ConfigUtil::TARGET_CLASS])) {
            $config[ConfigUtil::TARGET_TYPE] = 'to-one';
        }
        if (empty($config[ConfigUtil::DEPENDS_ON])) {
            unset($config[ConfigUtil::DEPENDS_ON]);
        }

        return $config;
    }
}
