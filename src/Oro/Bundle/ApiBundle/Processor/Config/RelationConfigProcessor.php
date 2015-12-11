<?php

namespace Oro\Bundle\ApiBundle\Processor\Config;

use Oro\Component\ChainProcessor\ActionProcessor;
use Oro\Bundle\ApiBundle\Processor\Config\GetRelationConfig\RelationConfigContext;

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
