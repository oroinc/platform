<?php

namespace Oro\Component\ChainProcessor\Tests\Unit;

use Oro\Component\ChainProcessor\ChainProcessor;
use Oro\Component\ChainProcessor\ContextInterface;

class ChainProcessorMock extends ChainProcessor
{
    /**
     * @param ContextInterface $context
     */
    public function process(ContextInterface $context)
    {
        $this->executeProcessors($context);
    }
}
