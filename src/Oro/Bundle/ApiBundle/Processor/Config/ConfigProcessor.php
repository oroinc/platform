<?php

namespace Oro\Bundle\ApiBundle\Processor\Config;

use Oro\Component\ChainProcessor\ActionProcessor;
use Oro\Bundle\ApiBundle\Processor\Config\GetConfig\ConfigContext;

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
