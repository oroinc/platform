<?php

namespace Oro\Bundle\MigrationBundle\Migration\Extension;

class DataStorageExtension
{
    /** @var array */
    protected $storage = [];

    /**
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return $this->has($key) ? $this->storage[$key] : $default;
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function has($key)
    {
        return array_key_exists($key, $this->storage);
    }

    /**
     * @param string $key
     * @param mixed  $value
     */
    public function put($key, $value)
    {
        $this->storage[$key] = $value;
    }
}
