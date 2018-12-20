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
     * {@inheritdoc}
     */
    public function configureEntityNode(NodeBuilder $node): void
    {
        parent::configureEntityNode($node);
        $node
            ->arrayNode(ConfigUtil::IDENTIFIER_FIELD_NAMES)
                ->prototype('scalar')->cannotBeEmpty()->end()
            ->end()
            ->booleanNode(ConfigUtil::DISABLE_INCLUSION)->end()
            ->booleanNode(ConfigUtil::DISABLE_FIELDSET)->end()
            ->booleanNode(ConfigUtil::DISABLE_META_PROPERTIES)->end()
            ->scalarNode(ConfigUtil::DELETE_HANDLER)->cannotBeEmpty()->end()
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
     * {@inheritdoc}
     */
    protected function postProcessConfig(array $config): array
    {
        $config = parent::postProcessConfig($config);
        if (empty($config[ConfigUtil::IDENTIFIER_FIELD_NAMES])) {
            unset($config[ConfigUtil::IDENTIFIER_FIELD_NAMES]);
        }
        if (empty($config[ConfigUtil::DOCUMENTATION_RESOURCE])) {
            unset($config[ConfigUtil::DOCUMENTATION_RESOURCE]);
        }

        return $config;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureFieldNode(NodeBuilder $node): void
    {
        parent::configureFieldNode($node);
        $node
            ->booleanNode(ConfigUtil::META_PROPERTY)->end();
    }
}
