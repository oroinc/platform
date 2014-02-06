<?php

namespace Oro\Bundle\EntityMergeBundle\Metadata;

use Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException;

class Metadata implements MetadataInterface
{
    /**
     * @var array
     */
    protected $options;

    /**
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (!is_array($options)) {
            throw new InvalidArgumentException('Options argument should have array type');
        }

        $this->options = $options;
    }

    /**
     * {inheritDoc}
     */
    public function get($code, $strict = false)
    {
        if (isset($this->options[$code])) {
            return $this->options[$code];
        }

        if ($strict) {
            throw new InvalidArgumentException(sprintf('Option "%s" not exists', $code));
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
        if ($filter) {
            return array_filter($this->options, $filter);
        }

        return $this->options;
    }
}
