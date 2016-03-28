<?php

namespace Oro\Bundle\ApiBundle\Config\Definition;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

use Oro\Bundle\ApiBundle\Config\ActionConfig;
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
            ->scalarNode(ActionConfig::ACL_RESOURCE)->end();
        $parentNode
            ->validate()
                ->always(
                    function ($value) use ($postProcessCallbacks, $sectionName) {
                        return $this->callProcessConfigCallbacks($value, $postProcessCallbacks, $sectionName);
                    }
                );
    }
}
