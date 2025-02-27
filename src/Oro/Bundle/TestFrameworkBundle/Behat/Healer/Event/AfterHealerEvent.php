<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Healer\Event;

use Behat\Testwork\Call\Call;
use Behat\Testwork\Call\CallResult;
use Behat\Testwork\Event\Event;
use Oro\Bundle\TestFrameworkBundle\Behat\Healer\HealerInterface;

/**
 * Represents an event right after healer is processed.
 */
class AfterHealerEvent extends Event
{
    public function __construct(
        protected HealerInterface $healer,
        protected Call $call,
        protected CallResult $callResult,
        protected string $healingId,
        protected float $execTime
    ) {
    }

    /**
     * Healing process ID will be the same for all healers that were performed to correct one step (at the moment)
     */
    public function getHealingId(): string
    {
        return $this->healingId;
    }

    public function getTime(): float
    {
        return $this->execTime;
    }

    public function getHealer(): HealerInterface
    {
        return $this->healer;
    }

    public function getCall(): Call
    {
        return $this->call;
    }

    public function getCallResult(): CallResult
    {
        return $this->callResult;
    }
}
