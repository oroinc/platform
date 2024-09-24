<?php

namespace Oro\Bundle\LayoutBundle\Layout;

use Oro\Component\Layout\ContextInterface;

class LayoutContextHolder
{
    /** @var ContextInterface|null */
    protected $context;

    public function getContext()
    {
        return $this->context;
    }

    public function setContext(ContextInterface $context)
    {
        $this->context = $context;
    }
}
