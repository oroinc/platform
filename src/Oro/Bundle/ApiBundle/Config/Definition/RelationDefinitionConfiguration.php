<?php

namespace Oro\Bundle\ApiBundle\Config\Definition;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

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
