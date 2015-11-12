<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Component\ChainProcessor\ActionProcessor;
use Oro\Bundle\ApiBundle\Processor\NormalizeValue\NormalizeValueContext;

class NormalizeValueProcessor extends ActionProcessor
{
    /**
     * {@inheritdoc}
     */
    public function createContext()
    {
        return new NormalizeValueContext();
    }
}
