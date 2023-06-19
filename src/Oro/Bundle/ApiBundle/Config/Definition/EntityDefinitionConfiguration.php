<?php

namespace Oro\Bundle\ApiBundle\Config\Definition;

use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * The configuration of elements in "entities" section.
 */
class EntityDefinitionConfiguration extends TargetEntityDefinitionConfiguration
{
    /**
     * {@inheritDoc}
     */
    public function configureEntityNode(NodeBuilder $node): void
    {
        parent::configureEntityNode($node);
        $node
            ->arrayNode(ConfigUtil::IDENTIFIER_FIELD_NAMES)
                ->performNoDeepMerging()
                ->prototype('scalar')->cannotBeEmpty()->end()
            ->end()
            ->booleanNode(ConfigUtil::DISABLE_INCLUSION)->end()
            ->booleanNode(ConfigUtil::DISABLE_FIELDSET)->end()
            ->arrayNode(ConfigUtil::DISABLE_META_PROPERTIES)
                ->treatFalseLike([false])
                ->treatTrueLike([true])
                ->prototype('scalar')->end()
            ->end()
            ->booleanNode(ConfigUtil::DISABLE_PARTIAL_LOAD)->end()
            ->arrayNode(ConfigUtil::INNER_JOIN_ASSOCIATIONS)
                ->prototype('scalar')->end()
            ->end()
            ->arrayNode(ConfigUtil::DOCUMENTATION_RESOURCE)
                ->beforeNormalization()
                    ->ifString()
                    ->then(function ($v) {
                        return [$v];
                    })
                ->end()
                ->prototype('scalar')->cannotBeEmpty()->end()
            ->end();
    }

    /**
     * {@inheritDoc}
     */
    protected function postProcessConfig(array $config): array
    {
        $config = parent::postProcessConfig($config);
        if (empty($config[ConfigUtil::IDENTIFIER_FIELD_NAMES])) {
            unset($config[ConfigUtil::IDENTIFIER_FIELD_NAMES]);
        }
        if (empty($config[ConfigUtil::INNER_JOIN_ASSOCIATIONS])) {
            unset($config[ConfigUtil::INNER_JOIN_ASSOCIATIONS]);
        }
        if (empty($config[ConfigUtil::DOCUMENTATION_RESOURCE])) {
            unset($config[ConfigUtil::DOCUMENTATION_RESOURCE]);
        }
        if (empty($config[ConfigUtil::DISABLE_META_PROPERTIES])) {
            unset($config[ConfigUtil::DISABLE_META_PROPERTIES]);
        }

        return $config;
    }

    /**
     * {@inheritDoc}
     */
    protected function configureFieldNode(NodeBuilder $node): void
    {
        parent::configureFieldNode($node);
        $node
            ->booleanNode(ConfigUtil::META_PROPERTY)->end();
    }
}
