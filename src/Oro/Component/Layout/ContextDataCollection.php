<?php

namespace Oro\Component\Layout;

class ContextDataCollection
{
    /** @var ContextInterface */
    private $context;

    /** @var array */
    private $items = [];

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
     * @param mixed  $value      The data to set
     */
    public function set($name, $value)
    {
        $this->items[$name] = $value;
    }

    /**
     * Sets callbacks to be used to get default value of the context data variable.
     *
     * @param string $name      The data item name
     * @param mixed $value      The default data item value or the callback method
     *                          to be used to get the default value
     *                          function (array|\ArrayAccess $options) : mixed
     *                          where $options argument represents the context variables
     *                          must throw an \BadMethodCallException if data cannot be loaded
     */
    public function setDefault($name, $value)
    {
        $this->defaults[$name] = $value;
    }

    /**
     * Removes a value stored in the context data variable.
     *
     * @param string $name The data item name
     */
    public function remove($name)
    {
        unset($this->items[$name]);
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

        $value = $this->defaults[$name];
        if (is_callable($value)) {
            try {
                $this->items[$name] = call_user_func($value, $this->context);
            } catch (\BadMethodCallException $e) {
                return false;
            }
        } else {
            $this->items[$name] = $value;
        }

        return true;
    }
}
