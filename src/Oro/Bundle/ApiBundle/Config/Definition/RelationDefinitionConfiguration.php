<?php

namespace Oro\Bundle\ApiBundle\Config\Definition;

use Symfony\Component\Config\Definition\Builder\NodeBuilder;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;

class RelationDefinitionConfiguration extends TargetEntityDefinitionConfiguration
{
    /**
     * {@inheritdoc}
     */
    public function configureEntityNode(NodeBuilder $node)
    {
        parent::configureEntityNode($node);
        $node
            ->booleanNode(EntityDefinitionFieldConfig::COLLAPSE)->end();
    }
}
