<?php

namespace Oro\Bundle\ApiBundle\Processor\GetConfig;

use Oro\Component\ChainProcessor\ActionProcessor;

/**
 * The main processor for "get_config" action.
 */
class ConfigProcessor extends ActionProcessor
{
    /**
     * {@inheritDoc}
     */
    protected function createContextObject(): ConfigContext
    {
        return new ConfigContext();
    }
}
