<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Component\ChainProcessor\ActionProcessor;
use Oro\Bundle\ApiBundle\Processor\BuildConfig\BuildConfigContext;

class BuildConfigProcessor extends ActionProcessor
{
    /**
     * {@inheritdoc}
     */
    protected function createContextObject()
    {
        return new BuildConfigContext();
    }
}
