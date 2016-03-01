<?php

namespace Oro\Bundle\ApiBundle\Processor\Config;

use Oro\Component\ChainProcessor\ActionProcessor;

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
