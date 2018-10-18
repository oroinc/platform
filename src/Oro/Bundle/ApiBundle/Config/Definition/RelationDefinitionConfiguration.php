<?php

namespace Oro\Bundle\ApiBundle\Config\Definition;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * The configuration of elements in "relations" section.
 */
class RelationDefinitionConfiguration extends TargetEntityDefinitionConfiguration
{
    /**
     * {@inheritdoc}
     */
    public function configureEntityNode(NodeBuilder $node): void
    {
        parent::configureEntityNode($node);
        $node
            ->booleanNode(ConfigUtil::COLLAPSE)->end();
    }
}
