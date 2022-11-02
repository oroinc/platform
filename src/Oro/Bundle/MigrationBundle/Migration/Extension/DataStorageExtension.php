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

    /**
     * {@inheritdoc}
     */
    public function has($key)
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * {@inheritdoc}
     */
    public function get($key, $default = null)
    {
        return array_key_exists($key, $this->data)
            ? $this->data[$key]
            : $default;
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value)
    {
        $this->data[$key] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function remove($key)
    {
        unset($this->data[$key]);
    }
}
