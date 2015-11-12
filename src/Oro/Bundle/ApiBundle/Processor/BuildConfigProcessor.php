<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Component\ChainProcessor\ActionProcessor;
use Oro\Bundle\ApiBundle\Processor\BuildConfig\BuildConfigContext;

class BuildConfigProcessor extends ActionProcessor
{
    /**
     * {@inheritdoc}
     */
    public function createContext()
    {
        return new BuildConfigContext();
    }
}
