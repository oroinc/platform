<?php

namespace Oro\Bundle\BatchBundle\Tests\Unit\ORM\Query\Stub;

use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIteratorInterface;

class BufferedQueryResultIteratorStub extends \ArrayIterator implements BufferedQueryResultIteratorInterface
{
    #[\Override]
    public function getSource()
    {
        return new \stdClass();
    }

    #[\Override]
    public function setBufferSize($bufferSize)
    {
    }

    #[\Override]
    public function setPageCallback(?callable $callback = null)
    {
    }

    #[\Override]
    public function setPageLoadedCallback(?callable $callback = null)
    {
    }

    #[\Override]
    public function setHydrationMode($hydrationMode)
    {
    }
}
