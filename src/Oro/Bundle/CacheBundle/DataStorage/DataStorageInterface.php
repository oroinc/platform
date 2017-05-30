<?php

namespace Oro\Bundle\CacheBundle\DataStorage;

interface DataStorageInterface
{
    /**
     * @param string $name
     *
     * @return mixed
     */
    public function get($name);

    /**
     * @param string $name
     * @param mixed  $value
     */
    public function set($name, $value);

    /**
     * @param string $name
     *
     * @return boolean
     */
    public function has($name);

    /**
     * @return array
     */
    public function all();

    /**
     * @param string $name
     */
    public function remove($name);
}
