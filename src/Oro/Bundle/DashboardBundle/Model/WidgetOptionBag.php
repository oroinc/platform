<?php

namespace Oro\Bundle\DashboardBundle\Model;

/**
 * Contains a list of widget options
 */
class WidgetOptionBag
{
    /**
     * @var array
     */
    protected $options = [];

    public function __construct(array $options = [])
    {
        $this->options = $options;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function has($name)
    {
        return array_key_exists($name, $this->options);
    }

    /**
     * @param string $name
     * @param mixed $default
     *
     * @return mixed
     */
    public function get($name, $default = null)
    {
        if (!$this->has($name)) {
            return $default;
        }

        return $this->options[$name];
    }

    /**
     * @return array
     */
    public function all()
    {
        return $this->options;
    }

    public function __serialize(): array
    {
        return $this->options;
    }

    public function __unserialize(array $serialized): void
    {
        $this->options = $serialized;
    }
}
