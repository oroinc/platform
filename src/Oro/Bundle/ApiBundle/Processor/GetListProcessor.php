<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Component\ChainProcessor\ActionProcessor;
use Oro\Bundle\ApiBundle\Processor\GetList\GetListContext;

class GetListProcessor extends ActionProcessor
{
    /**
     * {@inheritdoc}
     */
    public function createContext()
    {
        return new GetListContext();
    }
}
