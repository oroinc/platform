<?php

namespace Oro\Bundle\ApiBundle\Config\Definition;

use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * The configuration of elements in "actions" section.
 */
class ActionsConfiguration extends AbstractConfigurationSection
{
    /** @var string[] */
    protected $permissibleActions;

    /** @var string */
    protected $sectionName;

    /**
     * @param string[] $permissibleActions
     * @param string   $sectionName
     */
    public function __construct(array $permissibleActions, string $sectionName = 'actions.action')
    {
        $this->permissibleActions = $permissibleActions;
        $this->sectionName = $sectionName;
    }

    /**
     * {@inheritdoc}
     */
    public function configure(NodeBuilder $node): void
    {
        /** @var NodeBuilder $actionNode */
        $actionNode = $node->end()
            ->useAttributeAsKey('name')
            ->beforeNormalization()
                ->always(function ($value) {
                    return false === $value
                        ? \array_fill_keys($this->permissibleActions, false)
                        : $value;
                })
            ->end()
            ->validate()
                ->always(function ($value) {
                    $unknownActions = \array_diff(\array_keys($value), $this->permissibleActions);
                    if (!empty($unknownActions)) {
                        throw new \InvalidArgumentException(
                            \sprintf(
                                'The section "%s" contains not permissible actions: "%s". Permissible actions: "%s".',
                                $this->sectionName,
                                \implode(', ', $unknownActions),
                                \implode(', ', $this->permissibleActions)
                            )
                        );
                    }

                    return $value;
                })
            ->end()
            ->prototype('array')
                ->treatFalseLike([ConfigUtil::EXCLUDE => true])
                ->treatTrueLike([ConfigUtil::EXCLUDE => false])
                ->treatNullLike([ConfigUtil::EXCLUDE => false])
                ->children();
        $actionNode
            ->booleanNode(ConfigUtil::EXCLUDE)->end();
        $this->configureActionNode($actionNode);
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(string $section): bool
    {
        return 'entities.entity' === $section;
    }

    /**
     * @param NodeBuilder $node
     */
    protected function configureActionNode(NodeBuilder $node): void
    {
        $sectionName = $this->sectionName;

        /** @var ArrayNodeDefinition $parentNode */
        $parentNode = $node->end();
        $this->callConfigureCallbacks($node, $sectionName);
        $this->addPreProcessCallbacks($parentNode, $sectionName);
        $this->addPostProcessCallbacks(
            $parentNode,
            $sectionName,
            function ($value) {
                return $this->postProcessActionConfig($value);
            }
        );

        $node
            ->scalarNode(ConfigUtil::DESCRIPTION)->cannotBeEmpty()->end()
            ->scalarNode(ConfigUtil::DOCUMENTATION)->cannotBeEmpty()->end()
            ->scalarNode(ConfigUtil::ACL_RESOURCE)->end()
            ->integerNode(ConfigUtil::MAX_RESULTS)->min(-1)->end()
            ->integerNode(ConfigUtil::PAGE_SIZE)->min(-1)->end()
            ->arrayNode(ConfigUtil::ORDER_BY)
                ->performNoDeepMerging()
                ->useAttributeAsKey('name')
                ->prototype('enum')->values(['ASC', 'DESC'])->end()
            ->end()
            ->booleanNode(ConfigUtil::DISABLE_SORTING)->end()
            ->booleanNode(ConfigUtil::DISABLE_INCLUSION)->end()
            ->booleanNode(ConfigUtil::DISABLE_FIELDSET)->end()
            ->booleanNode(ConfigUtil::DISABLE_META_PROPERTIES)->end()
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
        $this->addStatusCodesNode($node);
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
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function postProcessActionConfig(array $config): array
    {
        if (\array_key_exists(ConfigUtil::PAGE_SIZE, $config)
            && -1 === $config[ConfigUtil::PAGE_SIZE]
            && !\array_key_exists(ConfigUtil::MAX_RESULTS, $config)
        ) {
            $config[ConfigUtil::MAX_RESULTS] = -1;
        }
        if (empty($config[ConfigUtil::ORDER_BY])) {
            unset($config[ConfigUtil::ORDER_BY]);
        }
        if (empty($config[ConfigUtil::STATUS_CODES])) {
            unset($config[ConfigUtil::STATUS_CODES]);
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
    public function addStatusCodesNode(NodeBuilder $node): void
    {
        /** @var ArrayNodeDefinition $parentNode */
        $codeNode = $node
            ->arrayNode(ConfigUtil::STATUS_CODES)
                ->useAttributeAsKey('name')
                ->prototype('array')
                    ->beforeNormalization()
                        ->ifString()
                        ->then(function ($value) {
                            return !empty($value) ? [ConfigUtil::DESCRIPTION => $value] : [];
                        })
                    ->end()
                    ->treatFalseLike([ConfigUtil::EXCLUDE => true])
                    ->treatTrueLike([ConfigUtil::EXCLUDE => false])
                    ->treatNullLike([])
                    ->children();
        $codeNode
            ->booleanNode(ConfigUtil::EXCLUDE)->end()
            ->scalarNode(ConfigUtil::DESCRIPTION)->cannotBeEmpty()->end();
        $this->configureStatusCodeNode($codeNode);
    }

    /**
     * @param NodeBuilder $node
     */
    protected function configureStatusCodeNode(NodeBuilder $node): void
    {
        $sectionName = $this->sectionName . '.status_code';

        /** @var ArrayNodeDefinition $parentNode */
        $parentNode = $node->end();
        $this->callConfigureCallbacks($node, $sectionName);
        $this->addPreProcessCallbacks($parentNode, $sectionName);
        $this->addPostProcessCallbacks($parentNode, $sectionName);
    }

    /**
     * @param NodeBuilder $node
     */
    protected function configureFieldNode(NodeBuilder $node): void
    {
        $sectionName = $this->sectionName . '.field';

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
            ->enumNode(ConfigUtil::DIRECTION)
                ->values([
                    ConfigUtil::DIRECTION_INPUT_ONLY,
                    ConfigUtil::DIRECTION_OUTPUT_ONLY,
                    ConfigUtil::DIRECTION_BIDIRECTIONAL
                ])
            ->end()
            ->scalarNode(ConfigUtil::FORM_TYPE)->end()
            ->arrayNode(ConfigUtil::FORM_OPTIONS)
                ->useAttributeAsKey('name')
                ->performNoDeepMerging()
                ->prototype('variable')->end()
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

        return $config;
    }
}
