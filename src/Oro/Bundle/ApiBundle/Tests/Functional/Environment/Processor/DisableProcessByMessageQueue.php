<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment\Processor;

use Oro\Bundle\ApiBundle\Processor\UpdateList\UpdateListContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

class DisableProcessByMessageQueue implements ProcessorInterface
{
    public function __construct(
        private readonly DisableProcessByMessageQueueState $processByMessageQueueState
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var UpdateListContext $context */

        if ($this->processByMessageQueueState->isProcessByMessageQueueDisabled()) {
            $context->setProcessByMessageQueue(false);
        }
    }
}
