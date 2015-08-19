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
     * Adds a new option or updates a value of existing option
     *
     * @param string $id          The item id
     * @param string $optionName  The option name or path
     * @param mixed  $optionValue The option value
     */
    public function setOption($id, $optionName, $optionValue);

    /**
     * Adds a new value in addition to existing one for a new or existing option
     *
     * @param string $id          The item id
     * @param string $optionName  The option name or path
     * @param mixed  $optionValue The option value to be added
     */
    public function appendOption($id, $optionName, $optionValue);

    /**
     * Removes existing value from existing option
     *
     * @param string $id          The item id
     * @param string $optionName  The option name or path
     * @param mixed  $optionValue The option value to be removed
     */
    public function subtractOption($id, $optionName, $optionValue);

    /**
     * Replaces one value with another value for existing option
     *
     * @param string $id             The item id
     * @param string $optionName     The option name or path
     * @param mixed  $oldOptionValue The option value to be replaced
     * @param mixed  $newOptionValue The option value to replace a value specified in $oldOptionValue parameter
     *
     * @return self
     */
    public function replaceOption($id, $optionName, $oldOptionValue, $newOptionValue);

    /**
     * Removes the option
     *
     * @param string $id         The item id
     * @param string $optionName The option name or path
     */
    public function removeOption($id, $optionName);
}
