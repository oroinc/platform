<?php

namespace Oro\Bundle\ApiBundle\Config\Definition;

use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * The configuration of elements in "actions" section.
 */
class ActionsConfiguration extends AbstractConfigurationSection
{
    /** @var string[] */
    private array $permissibleActions;
    private string $sectionName;

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
     * {@inheritDoc}
     */
    public function configure(NodeBuilder $node): void
    {
        /** @var NodeBuilder $actionNode */
        $actionNode = $node->end()
            ->useAttributeAsKey('')
            ->beforeNormalization()
                ->always(function ($value) {
                    return false === $value
                        ? array_fill_keys($this->permissibleActions, false)
                        : $value;
                })
            ->end()
            ->validate()
                ->always(function ($value) {
                    $unknownActions = array_diff(array_keys($value), $this->permissibleActions);
                    if (!empty($unknownActions)) {
                        throw new \InvalidArgumentException(sprintf(
                            'The section "%s" contains not permissible actions: "%s". Permissible actions: "%s".',
                            $this->sectionName,
                            implode(', ', $unknownActions),
                            implode(', ', $this->permissibleActions)
                        ));
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
     * {@inheritDoc}
     */
    public function isApplicable(string $section): bool
    {
        return 'entities.entity' === $section;
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private function configureActionNode(NodeBuilder $node): void
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
            ->booleanNode(ConfigUtil::DISABLE_PAGING)->end()
            ->integerNode(ConfigUtil::PAGE_SIZE)
                ->min(-1)
                ->validate()
                    ->always(function ($v) {
                        if (0 !== $v) {
                            return $v;
                        }
                        throw new \InvalidArgumentException('Expected a positive number or -1, but got 0.');
                    })
                ->end()
            ->end()
            ->arrayNode(ConfigUtil::ORDER_BY)
                ->performNoDeepMerging()
                ->useAttributeAsKey('')
                ->prototype('enum')->values(['ASC', 'DESC'])->end()
            ->end()
            ->booleanNode(ConfigUtil::DISABLE_SORTING)->end()
            ->booleanNode(ConfigUtil::DISABLE_INCLUSION)->end()
            ->booleanNode(ConfigUtil::DISABLE_FIELDSET)->end()
            ->arrayNode(ConfigUtil::DISABLE_META_PROPERTIES)
                ->treatFalseLike([false])
                ->treatTrueLike([true])
                ->prototype('scalar')->end()
            ->end()
            ->scalarNode(ConfigUtil::FORM_TYPE)->end()
            ->arrayNode(ConfigUtil::FORM_OPTIONS)
                ->useAttributeAsKey('')
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
                        throw new \InvalidArgumentException('The value must be a string or an array.');
                    })
                ->end()
            ->end()
            ->scalarNode(ConfigUtil::REQUEST_TARGET_CLASS)->end();

        $parentNode->end()->validate()
            ->always(function ($v) {
                foreach ($v as $action => $actionConfig) {
                    if (\array_key_exists(ConfigUtil::REQUEST_TARGET_CLASS, $actionConfig)
                        && ApiAction::UPDATE_SUBRESOURCE !== $action
                        && ApiAction::ADD_SUBRESOURCE !== $action
                        && ApiAction::DELETE_SUBRESOURCE !== $action
                    ) {
                        throw new \InvalidArgumentException(sprintf(
                            'The "%s" option is not allowed for the "%s" action.'
                            . ' This option is allowed for the following actions: "%s", "%s", "%s".',
                            ConfigUtil::REQUEST_TARGET_CLASS,
                            $action,
                            ApiAction::UPDATE_SUBRESOURCE,
                            ApiAction::ADD_SUBRESOURCE,
                            ApiAction::DELETE_SUBRESOURCE
                        ));
                    }
                }

                return $v;
            });

        $this->addStatusCodesNode($node);

        /** @var NodeBuilder $upsertNode */
        $upsertNode = $node
            ->arrayNode(ConfigUtil::UPSERT)
                ->treatFalseLike([ConfigUtil::UPSERT_DISABLE => true])
                ->treatTrueLike([ConfigUtil::UPSERT_DISABLE => false])
                ->children()
                    ->booleanNode(ConfigUtil::UPSERT_DISABLE)->end();
        $this->appendArrayOfNotEmptyStrings($upsertNode, ConfigUtil::UPSERT_ADD);
        $this->appendArrayOfNotEmptyStrings($upsertNode, ConfigUtil::UPSERT_REMOVE);
        $this->appendArrayOfNotEmptyStrings($upsertNode, ConfigUtil::UPSERT_REPLACE);

        $fieldNode = $node
            ->arrayNode(ConfigUtil::FIELDS)
                ->useAttributeAsKey('')
                ->normalizeKeys(false)
                ->prototype('array')
                    ->children();
        $this->configureFieldNode($fieldNode);
    }

    /**
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function postProcessActionConfig(array $config): array
    {
        if (\array_key_exists(ConfigUtil::DISABLE_PAGING, $config)) {
            if ($config[ConfigUtil::DISABLE_PAGING] && !\array_key_exists(ConfigUtil::PAGE_SIZE, $config)) {
                $config[ConfigUtil::PAGE_SIZE] = -1;
            }
            unset($config[ConfigUtil::DISABLE_PAGING]);
        }
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
        if (empty($config[ConfigUtil::DISABLE_META_PROPERTIES])) {
            unset($config[ConfigUtil::DISABLE_META_PROPERTIES]);
        }
        if (\array_key_exists(ConfigUtil::UPSERT, $config)) {
            if (empty($config[ConfigUtil::UPSERT][ConfigUtil::UPSERT_ADD])) {
                unset($config[ConfigUtil::UPSERT][ConfigUtil::UPSERT_ADD]);
            }
            if (empty($config[ConfigUtil::UPSERT][ConfigUtil::UPSERT_REMOVE])) {
                unset($config[ConfigUtil::UPSERT][ConfigUtil::UPSERT_REMOVE]);
            }
            if (empty($config[ConfigUtil::UPSERT][ConfigUtil::UPSERT_REPLACE])) {
                unset($config[ConfigUtil::UPSERT][ConfigUtil::UPSERT_REPLACE]);
            }
        }
        if (empty($config[ConfigUtil::FIELDS])) {
            unset($config[ConfigUtil::FIELDS]);
        }

        return $config;
    }

    public function addStatusCodesNode(NodeBuilder $node): void
    {
        /** @var ArrayNodeDefinition $parentNode */
        $codeNode = $node
            ->arrayNode(ConfigUtil::STATUS_CODES)
                ->useAttributeAsKey('')
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

    private function configureStatusCodeNode(NodeBuilder $node): void
    {
        $sectionName = $this->sectionName . '.status_code';

        /** @var ArrayNodeDefinition $parentNode */
        $parentNode = $node->end();
        $this->callConfigureCallbacks($node, $sectionName);
        $this->addPreProcessCallbacks($parentNode, $sectionName);
        $this->addPostProcessCallbacks($parentNode, $sectionName);
    }

    private function configureFieldNode(NodeBuilder $node): void
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
            ->scalarNode(ConfigUtil::PROPERTY_PATH)->cannotBeEmpty()->end()
            ->enumNode(ConfigUtil::DIRECTION)
                ->values([
                    ConfigUtil::DIRECTION_INPUT_ONLY,
                    ConfigUtil::DIRECTION_OUTPUT_ONLY,
                    ConfigUtil::DIRECTION_BIDIRECTIONAL
                ])
            ->end()
            ->scalarNode(ConfigUtil::FORM_TYPE)->end()
            ->arrayNode(ConfigUtil::FORM_OPTIONS)
                ->useAttributeAsKey('')
                ->performNoDeepMerging()
                ->prototype('variable')->end()
            ->end()
            ->scalarNode(ConfigUtil::POST_PROCESSOR)->end()
            ->arrayNode(ConfigUtil::POST_PROCESSOR_OPTIONS)
                ->useAttributeAsKey('')
                ->performNoDeepMerging()
                ->prototype('variable')->end()
            ->end();
    }

    private function postProcessFieldConfig(array $config): array
    {
        if (empty($config[ConfigUtil::FORM_TYPE])) {
            unset($config[ConfigUtil::FORM_TYPE]);
        }
        if (empty($config[ConfigUtil::FORM_OPTIONS])) {
            unset($config[ConfigUtil::FORM_OPTIONS]);
        }
        if (empty($config[ConfigUtil::POST_PROCESSOR])) {
            unset($config[ConfigUtil::POST_PROCESSOR]);
        }
        if (empty($config[ConfigUtil::POST_PROCESSOR_OPTIONS])) {
            unset($config[ConfigUtil::POST_PROCESSOR_OPTIONS]);
        }

        return $config;
    }

    private function appendArrayOfNotEmptyStrings(NodeBuilder $node, string $name): void
    {
        $node->arrayNode($name)
            ->variablePrototype()
                ->validate()
                    ->always(function (mixed $value) {
                        if (!\is_array($value)) {
                            throw new \InvalidArgumentException(sprintf(
                                'Expected "array", but got "%s"',
                                get_debug_type($value)
                            ));
                        }
                        foreach ($value as $val) {
                            if (!\is_string($val) || '' === $val) {
                                throw new \InvalidArgumentException('Expected array of not empty strings');
                            }
                        }

                        return $value;
                    });
    }
}
