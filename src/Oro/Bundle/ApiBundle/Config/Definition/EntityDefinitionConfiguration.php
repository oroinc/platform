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
                ->prototype('scalar')->end()
            ->end()
            ->booleanNode(EntityDefinitionConfig::DISABLE_INCLUSION)->end()
            ->booleanNode(EntityDefinitionConfig::DISABLE_FIELDSET)->end()
            ->scalarNode(EntityDefinitionConfig::DELETE_HANDLER)->cannotBeEmpty()->end();
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
