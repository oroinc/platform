<?php

namespace Oro\Bundle\EntityMergeBundle\Metadata;

/**
 * Defines the contract for metadata objects used in entity merge operations.
 *
 * This interface provides a common API for accessing and manipulating metadata options
 * stored as key-value pairs. Implementations can store merge configuration, Doctrine
 * mapping information, or other metadata relevant to entity merge operations. The interface
 * supports getting, setting, checking existence, and merging metadata values.
 */
interface MetadataInterface
{
    /**
     * @param  string $code
     * @param  bool   $strict
     * @return mixed
     */
    public function get($code, $strict = false);

    /**
     * @param string $code
     * @param mixed  $value
     */
    public function set($code, $value);

    /**
     * @param string $code
     * @return bool
     */
    public function has($code);

    /**
     * @param string $code
     * @param bool   $value
     * @return bool
     */
    public function is($code, $value = true);

    /**
     * @param \Closure|null $filter
     * @return array
     */
    public function all(?\Closure $filter = null);

    /**
     * Merges values of this object with another set of data
     *
     * @param MetadataInterface|array $data
     * @param bool $override
     */
    public function merge($data, $override = true);
}
