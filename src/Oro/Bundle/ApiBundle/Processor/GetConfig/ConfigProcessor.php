<?php

namespace Oro\Bundle\ApiBundle\Processor\GetConfig;

use Oro\Component\ChainProcessor\ActionProcessor;

/**
 * The main processor for "get_config" action.
 */
class ConfigProcessor extends ActionProcessor
{
    /**
     * {@inheritdoc}
     */
    protected function createContextObject()
    {
        return new ConfigContext();
    }
}
