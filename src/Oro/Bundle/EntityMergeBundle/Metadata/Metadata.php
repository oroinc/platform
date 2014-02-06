<?php

namespace Oro\Bundle\EntityMergeBundle\Metadata;

class Metadata implements MetadataInterface
{
    /**
     * @var array
     */
    protected $options;

    /**
     * @param array $options
     */
    public function __construct(array $options)
    {
        $this->options = $options;
    }

    /**
     * {inheritDoc}
     */
    public function get($code)
    {
        if (isset($this->options[$code])) {
            return $this->options[$code];
        }

        return null;
    }

    /**
     * {inheritDoc}
     */
    public function set($code, $value)
    {
        $this->options[$code] = $value;
    }

    /**
     * {inheritDoc}
     */
    public function has($code)
    {
        return isset($this->options[$code]);
    }

    /**
     * {inheritDoc}
     */
    public function is($code, $value = true)
    {
        return $this->get($code) === null ? false : $this->get($code) == $value;
    }

    /**
     * {inheritDoc}
     */
    public function all(\Closure $filter = null)
    {
        return $filter
            ? array_filter($this->values, $filter)
            : $this->options;
    }
}
