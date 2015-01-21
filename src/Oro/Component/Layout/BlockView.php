<?php

namespace Oro\Component\Layout;

use Symfony\Component\Form\FormView;

class BlockView extends FormView
{
    /**
     * @param BlockView $parent
     */
    public function __construct(BlockView $parent = null)
    {
        parent::__construct($parent);
    }
}
