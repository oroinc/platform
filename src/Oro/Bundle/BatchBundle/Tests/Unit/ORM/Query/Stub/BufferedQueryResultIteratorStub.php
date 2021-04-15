<?php

namespace Oro\Bundle\BatchBundle\Tests\Unit\ORM\Query\Stub;

use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIteratorInterface;

class BufferedQueryResultIteratorStub extends \ArrayIterator implements BufferedQueryResultIteratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function getSource()
    {
        return new \stdClass();
    }

    /**
     * {@inheritdoc}
     */
    public function setBufferSize($bufferSize)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function setPageCallback(callable $callback = null)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function setPageLoadedCallback(callable $callback = null)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function setHydrationMode($hydrationMode)
    {
    }
}
