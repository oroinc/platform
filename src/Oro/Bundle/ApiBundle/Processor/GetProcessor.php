<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Component\ChainProcessor\ActionProcessor;
use Oro\Bundle\ApiBundle\Processor\Get\GetContext;

class GetProcessor extends ActionProcessor
{
    /**
     * {@inheritdoc}
     */
    public function createContext()
    {
        return new GetContext();
    }
}
