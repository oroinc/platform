<?php

namespace Oro\Bundle\BatchBundle\Tests\Unit\Step\Stub;

use Oro\Bundle\BatchBundle\Item\Support\ClosableInterface;

class ClosableWriterStub extends WriterStub implements ClosableInterface
{
    public function close()
    {
    }
}
