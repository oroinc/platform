<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\GetFieldConfig;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

class LoadFromMetadata implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        $config = $context->getResult();
        if (null !== $config) {
            // a config already exists
            return;
        }

        $context->setResult(null);
    }
}
