<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Component\ChainProcessor\ChainProcessor;
use Oro\Component\ChainProcessor\ContextInterface;

class GetProcessor extends ChainProcessor
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        $this->executeProcessors($context);

        if (!$context->hasResult()) {
            throw new \RuntimeException('Unsupported request.');
        }
    }
}
