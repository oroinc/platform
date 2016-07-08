<?php

namespace Oro\Bundle\ApiBundle\Config\Definition;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

use Oro\Bundle\ApiBundle\Config\ActionConfig;
use Oro\Bundle\ApiBundle\Config\ActionFieldConfig;
use Oro\Bundle\ApiBundle\Config\StatusCodeConfig;

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
    public function __construct($permissibleActions, $sectionName = 'actions.action')
    {
        $this->permissibleActions = $permissibleActions;
        $this->sectionName = $sectionName;
    }

    /**
     * {@inheritdoc}
     */
    public function configure(NodeBuilder $node)
    {
        /** @var NodeBuilder $actionNode */
        $actionNode = $node->end()
            ->useAttributeAsKey('name')
            ->validate()
                ->always(function ($value) {
                    $unknownActions = array_diff(array_keys($value), $this->permissibleActions);
                    if (!empty($unknownActions)) {
                        throw new \InvalidArgumentException(
                            sprintf(
                                'The section "%s" contains not permissible actions: "%s". Permissible actions: "%s".',
                                $this->sectionName,
                                implode(', ', $unknownActions),
                                implode(', ', $this->permissibleActions)
                            )
                        );
                    }

                    return $value;
                })
            ->end()
            ->prototype('array')
                ->treatFalseLike([ActionConfig::EXCLUDE => true])
                ->treatTrueLike([ActionConfig::EXCLUDE => false])
                ->treatNullLike([ActionConfig::EXCLUDE => false])
                ->children();
        $actionNode
            ->booleanNode(ActionConfig::EXCLUDE)->end();
        $this->configureActionNode($actionNode);
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable($section)
    {
        return $section === 'entities.entity';
    }

    /**
     * @param NodeBuilder $node
     */
    protected function configureActionNode(NodeBuilder $node)
    {
        $sectionName = $this->sectionName;

        /** @var ArrayNodeDefinition $parentNode */
        $parentNode = $node->end();
        //$parentNode->ignoreExtraKeys(false); @todo: uncomment after migration to Symfony 2.8+
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
            ->scalarNode(ActionConfig::DESCRIPTION)->cannotBeEmpty()->end()
            ->scalarNode(ActionConfig::DOCUMENTATION)->cannotBeEmpty()->end()
            ->scalarNode(ActionConfig::ACL_RESOURCE)->end()
            ->integerNode(ActionConfig::MAX_RESULTS)->min(-1)->end()
            ->integerNode(ActionConfig::PAGE_SIZE)->min(-1)->end()
            ->arrayNode(ActionConfig::ORDER_BY)
                ->performNoDeepMerging()
                ->useAttributeAsKey('name')
                ->prototype('enum')->values(['ASC', 'DESC'])->end()
            ->end()
            ->booleanNode(ActionConfig::DISABLE_SORTING)->end()
            ->booleanNode(ActionConfig::DISABLE_INCLUSION)->end()
            ->booleanNode(ActionConfig::DISABLE_FIELDSET)->end()
            ->scalarNode(ActionConfig::FORM_TYPE)->end()
            ->arrayNode(ActionConfig::FORM_OPTIONS)
                ->useAttributeAsKey('name')
                ->performNoDeepMerging()
                ->prototype('variable')->end()
            ->end();
        $this->addStatusCodesNode($node);
        $fieldNode = $node
            ->arrayNode(ActionConfig::FIELDS)
                ->useAttributeAsKey('name')
                ->normalizeKeys(false)
                ->prototype('array')
                    ->children();
        $this->configureFieldNode($fieldNode);
    }

    /**
     * {@inheritdoc}
     */
    protected function postProcessActionConfig(array $config)
    {
        if (array_key_exists(ActionConfig::PAGE_SIZE, $config)
            && -1 === $config[ActionConfig::PAGE_SIZE]
            && !array_key_exists(ActionConfig::MAX_RESULTS, $config)
        ) {
            $config[ActionConfig::MAX_RESULTS] = -1;
        }
        if (empty($config[ActionConfig::ORDER_BY])) {
            unset($config[ActionConfig::ORDER_BY]);
        }
        if (empty($config[ActionConfig::STATUS_CODES])) {
            unset($config[ActionConfig::STATUS_CODES]);
        }
        if (empty($config[ActionConfig::FORM_TYPE])) {
            unset($config[ActionConfig::FORM_TYPE]);
        }
        if (empty($config[ActionConfig::FORM_OPTIONS])) {
            unset($config[ActionConfig::FORM_OPTIONS]);
        }
        if (empty($config[ActionConfig::FIELDS])) {
            unset($config[ActionConfig::FIELDS]);
        }

        return $config;
    }

    /**
     * @param NodeBuilder $node
     */
    public function addStatusCodesNode(NodeBuilder $node)
    {
        /** @var ArrayNodeDefinition $parentNode */
        $codeNode = $node
            ->arrayNode(ActionConfig::STATUS_CODES)
                ->useAttributeAsKey('name')
                ->prototype('array')
                    ->beforeNormalization()
                        ->ifString()
                        ->then(function ($value) {
                            return !empty($value) ? [StatusCodeConfig::DESCRIPTION => $value] : [];
                        })
                    ->end()
                    ->treatFalseLike([StatusCodeConfig::EXCLUDE => true])
                    ->treatTrueLike([StatusCodeConfig::EXCLUDE => false])
                    ->treatNullLike([])
                    ->children();
        $codeNode
            ->booleanNode(StatusCodeConfig::EXCLUDE)->end()
            ->scalarNode(StatusCodeConfig::DESCRIPTION)->cannotBeEmpty()->end();
        $this->configureStatusCodeNode($codeNode);
    }

    /**
     * @param NodeBuilder $node
     */
    protected function configureStatusCodeNode(NodeBuilder $node)
    {
        $sectionName = $this->sectionName . '.status_code';

        /** @var ArrayNodeDefinition $parentNode */
        $parentNode = $node->end();
        //$parentNode->ignoreExtraKeys(false); @todo: uncomment after migration to Symfony 2.8+
        $this->callConfigureCallbacks($node, $sectionName);
        $this->addPreProcessCallbacks($parentNode, $sectionName);
        $this->addPostProcessCallbacks($parentNode, $sectionName);
    }

    /**
     * @param NodeBuilder $node
     */
    protected function configureFieldNode(NodeBuilder $node)
    {
        $sectionName = $this->sectionName . '.field';

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
            ->booleanNode(ActionFieldConfig::EXCLUDE)->end()
            ->scalarNode(ActionFieldConfig::FORM_TYPE)->end()
            ->arrayNode(ActionFieldConfig::FORM_OPTIONS)
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
    protected function postProcessFieldConfig(array $config)
    {
        if (empty($config[ActionFieldConfig::FORM_TYPE])) {
            unset($config[ActionFieldConfig::FORM_TYPE]);
        }
        if (empty($config[ActionFieldConfig::FORM_OPTIONS])) {
            unset($config[ActionFieldConfig::FORM_OPTIONS]);
        }

        return $config;
    }
}
