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
     * @var string
     */
    private $id;

    /**
     * All layout block views.
     *
     * @var BlockView[]
     */
    public $blocks = [];

    /**
     * @param string $id
     * @param BlockView $parent
     */
    public function __construct($id = '', BlockView $parent = null)
    {
        parent::__construct($parent);

        $this->id = $id;

        unset($this->vars['value']);
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }
}
