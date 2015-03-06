<?php

namespace Oro\Component\Layout;

class ContextDataCollection
{
    /** @var ContextInterface */
    private $context;

    /** @var array */
    private $items = [];

    /** @var array */
    private $ids = [];

    /** @var array */
    private $defaults = [];

    /**
     * @param ContextInterface $context
     */
    public function __construct(ContextInterface $context)
    {
        $this->context = $context;
    }

    /**
     * Returns an unique identifier of tied data.
     *
     * @param string $name The data item name
     *
     * @return string
     */
    public function getIdentifier($name)
    {
        if (!isset($this->ids[$name])
            && !array_key_exists($name, $this->ids)
            && !$this->applyDefaultValue($name)
        ) {
            throw new \OutOfBoundsException(sprintf('Undefined data item index: %s.', $name));
        };

        return $this->ids[$name];
    }

    /**
     * Checks whether the context contains the given data value.
     *
     * @param string $name The data item name
     *
     * @return bool
     */
    public function has($name)
    {
        return
            isset($this->items[$name])
            || array_key_exists($name, $this->items)
            || $this->applyDefaultValue($name);
    }

    /**
     * Gets a value stored in the context data variable.
     *
     * @param string $name The data item name
     *
     * @return mixed
     *
     * @throws \OutOfBoundsException if the data item does not exist
     */
    public function get($name)
    {
        if (!isset($this->items[$name])
            && !array_key_exists($name, $this->items)
            && !$this->applyDefaultValue($name)
        ) {
            throw new \OutOfBoundsException(sprintf('Undefined data item index: %s.', $name));
        };

        return $this->items[$name];
    }

    /**
     * Sets a value in the context data variable.
     *
     * @param string $name       The data item name
     * @param mixed  $identifier The unique identifier of tied data
     * @param mixed  $value      The data to set
     */
    public function set($name, $identifier, $value)
    {
        $this->ids[$name]   = $identifier;
        $this->items[$name] = $value;
    }

    /**
     * Sets callbacks to be used to get default unique identifier and value of the context data variable.
     *
     * @param string   $name               The data item name
     * @param \Closure $identifierCallback The callback method to be used to get the unique identifier of tied data
     *                                     function (array|\ArrayAccess $options) : string
     *                                     where $options argument represents the context variables
     * @param \Closure $valueCallback      The callback method to be used to get data
     *                                     function (array|\ArrayAccess $options) : mixed
     *                                     where $options argument represents the context variables
     *                                     must throw an \BadMethodCallException if data cannot be loaded
     */
    public function setDefault($name, \Closure $identifierCallback, \Closure $valueCallback)
    {
        $this->defaults[$name] = [$identifierCallback, $valueCallback];
    }

    /**
     * Removes a value stored in the context data variable.
     *
     * @param string $name The data item name
     */
    public function remove($name)
    {
        unset($this->ids[$name], $this->items[$name]);
    }

    /**
     * Returns names of all registered data items.
     *
     * @return string[]
     */
    public function getKnownValues()
    {
        return array_unique(array_merge(array_keys($this->items), array_keys($this->defaults)));
    }

    /**
     * @param string $name The data item name
     *
     * @return bool true if the default value has been applied; otherwise, false
     */
    protected function applyDefaultValue($name)
    {
        if (!isset($this->defaults[$name])) {
            return false;
        }

        try {
            $this->items[$name] = call_user_func($this->defaults[$name][1], $this->context);
        } catch (\BadMethodCallException $e) {
            return false;
        }

        $this->ids[$name] = call_user_func($this->defaults[$name][0], $this->context);

        return true;
    }
}
