<?php

namespace Oro\Bundle\ApiBundle\Processor\Config;

use Oro\Bundle\ApiBundle\Processor\Config\GetRelationConfig\RelationConfigContext;
use Oro\Component\ChainProcessor\ActionProcessor;

/**
 * The main processor for "get_relation_config" action.
 */
class RelationConfigProcessor extends ActionProcessor
{
    /**
     * {@inheritdoc}
     */
    protected function createContextObject()
    {
        return new RelationConfigContext();
    }
}
