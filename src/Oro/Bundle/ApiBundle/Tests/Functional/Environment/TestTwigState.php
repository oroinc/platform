<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment;

class TestTwigState
{
    private ?bool $enabled = null;

    public function enableTwig(?bool $enabled): void
    {
        if (null !== $enabled) {
            $this->enabled = $enabled;
        } elseif (null === $this->enabled) {
            $this->enabled = false;
        }
    }

    public function isTwigEnabled(): bool
    {
        return false !== $this->enabled;
    }
}
