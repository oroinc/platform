<?php

namespace Oro\Bundle\MigrationBundle\Migration;

/**
 * Defines the contract for a key-value storage used during migration and fixture execution.
 *
 * This interface provides a simple storage mechanism for migrations and fixtures to share data
 * during execution. It supports checking for key existence, retrieving values with defaults,
 * setting values, and removing keys.
 */
interface DataStorageInterface
{
    /**
     * @param string $key
     *
     * @return bool
     */
    public function has($key);

    /**
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function get($key, $default = null);

    /**
     * @param string $key
     * @param mixed  $value
     */
    public function set($key, $value);

    /**
     * @param string $key
     */
    public function remove($key);
}
