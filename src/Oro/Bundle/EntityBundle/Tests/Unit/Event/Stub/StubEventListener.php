<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Event\Stub;

class StubEventListener
{
    public bool $isFlushed = false;

    public function postFlush(): void
    {
        $this->isFlushed = true;
    }
}
