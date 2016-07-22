<?php

namespace Oro\Bundle\LayoutBundle\Layout;

use Oro\Component\Layout\LayoutContext;

class LayoutContextHolder
{
    /** @var LayoutContext|null */
    protected $context;

    /**
     * {@inheritdoc}
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @param LayoutContext $context
     */
    public function setContext(LayoutContext $context)
    {
        $this->context = $context;
    }
}
