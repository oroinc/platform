<?php

namespace Oro\Component\Layout;

/**
 * In additional to LayoutModifierInterface provides methods to manage the layout item options
 * The options related operation are available for the layout built without the block types
 */
interface RawLayoutModifierInterface extends LayoutModifierInterface
{
    /**
     * Adds a new option or updates a value of existing option for the item
     *
     * @param string $id          The item id
     * @param string $optionName  The option name
     * @param mixed  $optionValue The option value
     *
     * @return self
     */
    public function setOption($id, $optionName, $optionValue);

    /**
     * Removes the option for the item
     *
     * @param string $id         The item id
     * @param string $optionName The option name
     *
     * @return self
     */
    public function removeOption($id, $optionName);
}
