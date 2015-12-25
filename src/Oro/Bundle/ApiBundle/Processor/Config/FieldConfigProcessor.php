<?php

namespace Oro\Bundle\ApiBundle\Processor\Config;

use Oro\Component\ChainProcessor\ActionProcessor;
use Oro\Bundle\ApiBundle\Processor\Config\GetFieldConfig\FieldConfigContext;

class FieldConfigProcessor extends ActionProcessor
{
    /**
     * {@inheritdoc}
     */
    protected function createContextObject()
    {
        return new FieldConfigContext();
    }
}
