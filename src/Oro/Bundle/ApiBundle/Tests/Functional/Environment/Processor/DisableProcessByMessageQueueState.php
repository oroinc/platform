<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment\Processor;

class DisableProcessByMessageQueueState
{
    private bool $disableProcessByMessageQueue = false;

    public function disableProcessByMessageQueue(): void
    {
        $this->disableProcessByMessageQueue = true;
    }

    public function isProcessByMessageQueueDisabled(): bool
    {
        return $this->disableProcessByMessageQueue;
    }
}
