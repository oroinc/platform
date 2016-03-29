<?php

namespace Oro\Bundle\ApiBundle\Config\Definition;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

use Oro\Bundle\ApiBundle\Config\ActionConfig;
use Oro\Bundle\ApiBundle\Config\StatusCodeConfig;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class ActionsConfiguration extends AbstractConfigurationSection implements ConfigurationSectionInterface
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
        /** @var NodeBuilder $actionNode */
        $actionNode = $node->end()
            ->useAttributeAsKey('name')
            ->prototype('array')
                ->treatFalseLike([ActionConfig::EXCLUDE => true])
                ->treatTrueLike([ActionConfig::EXCLUDE => false])
                ->treatNullLike([ActionConfig::EXCLUDE => false])
                ->children();
        $actionNode
            ->booleanNode(ActionConfig::EXCLUDE)->cannotBeEmpty()->end();
        $this->configureActionNode($actionNode, $configureCallbacks, $preProcessCallbacks, $postProcessCallbacks);
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
     * @param array       $configureCallbacks
     * @param array       $preProcessCallbacks
     * @param array       $postProcessCallbacks
     */
    protected function configureActionNode(
        NodeBuilder $node,
        array $configureCallbacks,
        array $preProcessCallbacks,
        array $postProcessCallbacks
    ) {
        $sectionName = ConfigUtil::ACTIONS . '.action';

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
            ->scalarNode(ActionConfig::ACL_RESOURCE)->end()
            ->scalarNode(ActionConfig::DESCRIPTION)->end()
            ->integerNode(ActionConfig::MAX_RESULTS)
                ->min(-1)
            ->end();
        $this->addStatusCodesNode(
            $node,
            $configureCallbacks,
            $preProcessCallbacks,
            $postProcessCallbacks
        );
        $parentNode
            ->validate()
                ->always(
                    function ($value) use ($postProcessCallbacks, $sectionName) {
                        if (empty($value[ActionConfig::STATUS_CODES])) {
                            unset($value[ActionConfig::STATUS_CODES]);
                        }
                        return $this->callProcessConfigCallbacks($value, $postProcessCallbacks, $sectionName);
                    }
                );
    }

    /**
     * {@inheritdoc}
     */
    public function addStatusCodesNode(
        NodeBuilder $node,
        array $configureCallbacks,
        array $preProcessCallbacks,
        array $postProcessCallbacks
    ) {
        /** @var ArrayNodeDefinition $parentNode */
        $codeNode = $node
            ->arrayNode(ConfigUtil::STATUS_CODES)
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
            ->booleanNode(StatusCodeConfig::EXCLUDE)->cannotBeEmpty()->end()
            ->scalarNode(StatusCodeConfig::DESCRIPTION)->cannotBeEmpty()->end();
        $this->configureStatusCodeNode($codeNode, $configureCallbacks, $preProcessCallbacks, $postProcessCallbacks);
    }

    /**
     * @param NodeBuilder $node
     * @param array       $configureCallbacks
     * @param array       $preProcessCallbacks
     * @param array       $postProcessCallbacks
     */
    protected function configureStatusCodeNode(
        NodeBuilder $node,
        array $configureCallbacks,
        array $preProcessCallbacks,
        array $postProcessCallbacks
    ) {
        $sectionName = 'actions.action.status_code';

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
        $parentNode
            ->validate()
                ->always(
                    function ($value) use ($postProcessCallbacks, $sectionName) {
                        return $this->callProcessConfigCallbacks($value, $postProcessCallbacks, $sectionName);
                    }
                );
    }
}
