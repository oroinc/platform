<?php

namespace Oro\Bundle\LoggerBundle\Tests\Unit\Stub;

use Monolog\Handler\TestHandler as BaseHandler;

class TestHandler extends BaseHandler
{
    public function reset()
    {
        $this->records = [];
    }
}
