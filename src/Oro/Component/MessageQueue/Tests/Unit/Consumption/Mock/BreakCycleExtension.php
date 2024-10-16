<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Consumption\Mock;

use Oro\Component\MessageQueue\Consumption\AbstractExtension;
use Oro\Component\MessageQueue\Consumption\Context;

class BreakCycleExtension extends AbstractExtension
{
    protected $cycles = 1;

    private $limit;

    public function __construct($limit)
    {
        $this->limit = $limit;
    }

    #[\Override]
    public function onPostReceived(Context $context)
    {
        $this->onIdle($context);
    }

    #[\Override]
    public function onIdle(Context $context)
    {
        if ($this->cycles >= $this->limit) {
            $context->setExecutionInterrupted(true);
        } else {
            $this->cycles++;
        }
    }
}
