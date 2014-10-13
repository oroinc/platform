<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\Fixtures;

use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface;

class TestListener implements OptionalListenerInterface
{
    public $enabled;

    public function setEnabled($enabled = true)
    {
        $this->enabled = $enabled;
    }
}
