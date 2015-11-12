<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Component\ChainProcessor\ActionProcessor;
use Oro\Bundle\ApiBundle\Processor\GetConfig\GetConfigContext;

class GetConfigProcessor extends ActionProcessor
{
    /**
     * {@inheritdoc}
     */
    public function createContext()
    {
        return new GetConfigContext();
    }
}
