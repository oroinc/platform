<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Bundle\ApiBundle\Processor\GetMetadata\MetadataContext;
use Oro\Component\ChainProcessor\ActionProcessor;

/**
 * The main processor for "get_metadata" action.
 */
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
