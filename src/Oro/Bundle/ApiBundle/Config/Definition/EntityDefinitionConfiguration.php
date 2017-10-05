<?php

namespace Oro\Bundle\ApiBundle\Config\Definition;

use Symfony\Component\Config\Definition\Builder\NodeBuilder;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;

class EntityDefinitionConfiguration extends TargetEntityDefinitionConfiguration
{
    /**
     * {@inheritdoc}
     */
    public function configureEntityNode(NodeBuilder $node)
    {
        parent::configureEntityNode($node);
        $node
            ->arrayNode(EntityDefinitionConfig::IDENTIFIER_FIELD_NAMES)
                ->prototype('scalar')->cannotBeEmpty()->end()
            ->end()
            ->booleanNode(EntityDefinitionConfig::DISABLE_INCLUSION)->end()
            ->booleanNode(EntityDefinitionConfig::DISABLE_FIELDSET)->end()
            ->booleanNode(EntityDefinitionConfig::DISABLE_META_PROPERTIES)->end()
            ->scalarNode(EntityDefinitionConfig::DELETE_HANDLER)->cannotBeEmpty()->end()
            ->arrayNode(EntityDefinitionConfig::DOCUMENTATION_RESOURCE)
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
    protected function postProcessConfig(array $config)
    {
        $config = parent::postProcessConfig($config);
        if (empty($config[EntityDefinitionConfig::IDENTIFIER_FIELD_NAMES])) {
            unset($config[EntityDefinitionConfig::IDENTIFIER_FIELD_NAMES]);
        }
        if (empty($config[EntityDefinitionConfig::DOCUMENTATION_RESOURCE])) {
            unset($config[EntityDefinitionConfig::DOCUMENTATION_RESOURCE]);
        }

        return $config;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureFieldNode(NodeBuilder $node)
    {
        parent::configureFieldNode($node);
        $node
            ->booleanNode(EntityDefinitionFieldConfig::META_PROPERTY)->end();
    }
}
