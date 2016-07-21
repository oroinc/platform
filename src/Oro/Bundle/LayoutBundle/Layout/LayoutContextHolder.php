<?php

namespace Oro\Bundle\LayoutBundle\Layout;

use Oro\Component\Layout\LayoutContext;

class LayoutContextHolder
{
    /** @var null */
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
    public function setContext(LayoutContext $context = null)
    {
        $this->context = $context;
    }
}
