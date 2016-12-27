<?php

namespace Oro\Component\Layout\Tests\Unit\Stubs;

use Oro\Component\Layout\ContextItemInterface;

class ContextItemStub implements ContextItemInterface
{

    /**
     * {@inheritdoc}
     */
    public function toString()
    {
        return 'id:1';
    }

    /**
     * Return a hash of the object.
     * This string is used as a part of the key for the layout cache.
     *
     * @return string
     */
    public function getHash()
    {
        return $this->toString();
    }
}
