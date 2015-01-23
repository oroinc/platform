<?php

namespace Oro\Component\Layout;

class Layout
{
    /** @var BlockView */
    protected $view;

    /**
     * @param BlockView $view
     */
    public function __construct(BlockView $view)
    {
        $this->view = $view;
    }

    /**
     * @return BlockView
     */
    public function getView()
    {
        return $this->view;
    }
}
