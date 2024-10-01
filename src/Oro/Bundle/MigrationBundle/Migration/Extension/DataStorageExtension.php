<?php

namespace Oro\Bundle\MigrationBundle\Migration\Extension;

use Oro\Bundle\MigrationBundle\Migration\DataStorageInterface;

/**
 * This extension can be used if you need to exchange data between different migrations
 */
class DataStorageExtension implements DataStorageInterface
{
    /** @var array */
    private $data = [];

    #[\Override]
    public function has($key)
    {
        return array_key_exists($key, $this->data);
    }

    #[\Override]
    public function get($key, $default = null)
    {
        return array_key_exists($key, $this->data)
            ? $this->data[$key]
            : $default;
    }

    #[\Override]
    public function set($key, $value)
    {
        $this->data[$key] = $value;
    }

    #[\Override]
    public function remove($key)
    {
        unset($this->data[$key]);
    }
}
