<?php

namespace Oro\Component\Layout;

use Symfony\Component\Form\FormView;

class BlockView extends FormView
{
    /**
     * The list of names if block types based on which this view is built
     *
     * @var array key = name, value true
     */
    private $types;

    /**
     * @param BlockView $parent
     */
    public function __construct(array $types, BlockView $parent = null)
    {
        parent::__construct($parent);
        $this->types = array_fill_keys($types, true);
    }

    /**
     * Checks whether this view is built based on the given block type
     *
     * @param string $blockType The name of the block type
     *
     * @return bool
     */
    public function isInstanceOf($blockType)
    {
        return isset($this->types[$blockType]);
    }
}
