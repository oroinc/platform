<?php

namespace Oro\Component\Layout;

class LayoutContext implements ContextInterface
{
    /** @var array */
    protected $data = [];

    /**
     * {@inheritdoc}
     */
    public function has($name)
    {
        return isset($this->data[$name]) || array_key_exists($name, $this->data);
    }

    /**
     * {@inheritdoc}
     */
    public function get($name)
    {
        if (!isset($this->data[$name]) && !array_key_exists($name, $this->data)) {
            throw new \OutOfBoundsException(sprintf('Undefined index: %s.', $name));
        };

        return $this->data[$name];
    }

    /**
     * {@inheritdoc}
     */
    public function set($name, $value)
    {
        $this->data[$name] = $value;
    }
}
