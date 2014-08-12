<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Event\Stub;

class StubEventListener
{
    /**
     * @var bool
     */
    public $isFlushed = false;

    public function postFlush()
    {
        $this->isFlushed = true;
    }
}
