<?php

namespace Oro\Component\Layout;

/**
 * In additional to the layout manipulation methods provided by RawLayoutManipulatorInterface
 * provides methods to check current state of the layout
 */
interface RawLayoutAccessorInterface extends RawLayoutManipulatorInterface
{
    /**
     * Checks whether the item with the given id exists in the layout
     *
     * @param string $id The item id
     *
     * @return bool
     */
    public function has($id);

    /**
     * Checks whether the given item alias exists
     *
     * @param string $alias The item alias
     *
     * @return bool
     */
    public function hasAlias($alias);

    /**
     * Returns all options for the given layout item
     *
     * @param string $id The item id
     *
     * @return array
     */
    public function getOptions($id);
}
