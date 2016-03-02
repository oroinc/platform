<?php

namespace Oro\Component\Layout;

use Symfony\Component\Form\FormView;

/**
 * @method BlockView getParent()
 * @property BlockView[] children
 */
class BlockView extends FormView
{
    /**
     * All layout block views.
     *
     * @var BlockView[]
     */
    public $blocks = [];

    /**
     * @param BlockView $parent
     */
    public function __construct(BlockView $parent = null)
    {
        parent::__construct($parent);
        unset($this->vars['value']);
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function addToVars($name, $value)
    {
        $this->vars[$name] = $value;

        foreach ($this->children as $child) {
            $child->addToVars($name, $value);
        }
    }
}
