<?php

namespace Oro\Bundle\LayoutBundle\Assetic;

use Assetic\Factory\Resource\ResourceInterface;

class LayoutResource implements ResourceInterface
{
    public function isFresh($timestamp)
    {
        return true;
    }

    public function getContent()
    {
        return [];
    }

    public function __toString()
    {
        return 'layout';
    }
}
