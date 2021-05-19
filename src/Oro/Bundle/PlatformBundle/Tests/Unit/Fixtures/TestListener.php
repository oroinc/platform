<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\Fixtures;

use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerTrait;

class TestListener implements OptionalListenerInterface
{
    use OptionalListenerTrait;

    public function getEnabled(): bool
    {
        return $this->enabled;
    }

    public function resetEnabled(): void
    {
        $this->enabled = false;
    }
}
