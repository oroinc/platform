<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Bundle\ApiBundle\Processor\GetMetadata\MetadataContext;
use Oro\Component\ChainProcessor\ActionProcessor;

class MetadataProcessor extends ActionProcessor
{
    /**
     * {@inheritdoc}
     */
    protected function createContextObject()
    {
        return new MetadataContext();
    }
}
