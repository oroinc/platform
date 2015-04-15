<?php

namespace Oro\Component\Layout;

interface BlockOptionsManipulatorInterface
{
    /**
     * Sets the RawLayout
     *
     * @param RawLayout $rawLayout The raw layout instance
     */
    public function setRawLayout(RawLayout $rawLayout);

    /**
     * Adds a new option or updates a value of existing option for the item
     *
     * @param string $id          The item id
     * @param string $optionName  The option name or path
     * @param mixed  $optionValue The option value
     */
    public function setOption($id, $optionName, $optionValue);

    /**
     * Adds a new value in additional to existing one for a new or existing option of the item
     *
     * @param string $id          The item id
     * @param string $optionName  The option name or path
     * @param mixed  $optionValue The option value to be added
     */
    public function appendOption($id, $optionName, $optionValue);

    /**
     * Removes existing value from existing option of the item
     *
     * @param string $id          The item id
     * @param string $optionName  The option name or path
     * @param mixed  $optionValue The option value to be removed
     */
    public function subtractOption($id, $optionName, $optionValue);

    /**
     * Removes the option for the item
     *
     * @param string $id         The item id
     * @param string $optionName The option name or path
     */
    public function removeOption($id, $optionName);
}
