<?php

namespace Oro\Bundle\EntityMergeBundle\Metadata;

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
     * @param callable $filter
     * @return array
     */
    public function all(\Closure $filter = null);
}
