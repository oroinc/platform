<?php

namespace Oro\Component\Layout;

use Symfony\Component\Form\FormView;

/**
 * @method BlockView getParent()
 */
class BlockView extends FormView
{
    /**
     * @param BlockView $parent
     */
    public function __construct(BlockView $parent = null)
    {
        parent::__construct($parent);
        unset($this->vars['value']);
    }

    /**
     * Returns a child from any level of a hierarchy by id (implements \ArrayAccess)
     *
     * @param string $id The child id
     *
     * @return BlockView The child view
     *
     * @throws \OutOfBoundsException if a child does not exist
     */
    public function offsetGet($id)
    {
        if (isset($this->children[$id])) {
            return $this->children[$id];
        };
        foreach ($this->children as $child) {
            if (isset($child[$id])) {
                return $child[$id];
            };
        }

        throw new \OutOfBoundsException(sprintf('Undefined index: %s.', $id));
    }

    /**
     * Checks whether the given child exists on any level of a hierarchy (implements \ArrayAccess)
     *
     * @param string $id The child id
     *
     * @return bool Whether the child view exists
     */
    public function offsetExists($id)
    {
        if (isset($this->children[$id])) {
            return true;
        };
        foreach ($this->children as $child) {
            if (isset($child[$id])) {
                return true;
            };
        }

        return false;
    }

    /**
     * Implements \ArrayAccess
     *
     * @throws \BadMethodCallException always as setting a child by id is not allowed
     */
    public function offsetSet($id, $value)
    {
        throw new \BadMethodCallException('Not supported');
    }

    /**
     * Implements \ArrayAccess
     *
     * @throws \BadMethodCallException always as removing a child by id is not allowed
     */
    public function offsetUnset($id)
    {
        throw new \BadMethodCallException('Not supported');
    }
}
