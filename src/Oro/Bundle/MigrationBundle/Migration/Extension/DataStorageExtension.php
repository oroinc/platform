<?php

namespace Oro\Bundle\MigrationBundle\Migration\Extension;

class DataStorageExtension
{
    /** @var array */
    protected $storage;

    /** @var DataStorageExtension */
    protected static $instance;

    public function __construct()
    {
        $this->storage = [];
    }

    /**
     * @param mixed $key
     *
     * @return mixed
     */
    public function get($key)
    {
        return $this->storage[$key];
    }

    /**
     * @param mixed $key
     *
     * @return bool
     */
    public function has($key)
    {
        return in_array($key, $this->storage);
    }

    /**
     * @param $key
     * @param $value
     */
    public function put($key, $value)
    {
        $this->storage[$key] = $value;
    }
}
