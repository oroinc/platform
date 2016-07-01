<?php

namespace Oro\Bundle\ApiBundle\Config\Definition;

use Symfony\Component\Config\Definition\Builder\NodeBuilder;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;

class EntityDefinitionConfiguration extends TargetEntityDefinitionConfiguration
{
    /**
     * {@inheritdoc}
     */
    public function configureEntityNode(NodeBuilder $node)
    {
        parent::configureEntityNode($node);
        $node
            ->integerNode(EntityDefinitionConfig::PAGE_SIZE)
                ->min(-1)
            ->end()
            ->booleanNode(EntityDefinitionConfig::DISABLE_SORTING)->end()
            ->arrayNode(EntityDefinitionConfig::IDENTIFIER_FIELD_NAMES)->prototype('scalar')->end()->end()
            ->scalarNode(EntityDefinitionConfig::DELETE_HANDLER)->cannotBeEmpty()->end();
    }

    /**
     * @param array $config
     *
     * @return array
     */
    protected function postProcessConfig(array $config)
    {
        $config = parent::postProcessConfig($config);
        if (array_key_exists(EntityDefinitionConfig::PAGE_SIZE, $config)
            && -1 === $config[EntityDefinitionConfig::PAGE_SIZE]
            && !array_key_exists(EntityDefinitionConfig::MAX_RESULTS, $config)
        ) {
            $config[EntityDefinitionConfig::MAX_RESULTS] = -1;
        }
        if (array_key_exists(EntityDefinitionConfig::DISABLE_SORTING, $config)
            && !$config[EntityDefinitionConfig::DISABLE_SORTING]
        ) {
            unset($config[EntityDefinitionConfig::DISABLE_SORTING]);
        }
        if (empty($config[EntityDefinitionConfig::IDENTIFIER_FIELD_NAMES])) {
            unset($config[EntityDefinitionConfig::IDENTIFIER_FIELD_NAMES]);
        }

        return $config;
    }
}
