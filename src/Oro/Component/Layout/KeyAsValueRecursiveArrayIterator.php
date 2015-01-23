<?php

namespace Oro\Component\Layout;

class KeyAsValueRecursiveArrayIterator extends \RecursiveArrayIterator
{
    /**
     * {@inheritdoc}
     */
    public function current()
    {
        return $this->key();
    }
}
