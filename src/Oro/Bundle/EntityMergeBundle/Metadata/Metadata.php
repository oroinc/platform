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
    public function __construct(array $options = [])
    {
        $this->options = $options;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function set($code, $value)
    {
        $this->options[$code] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function has($code)
    {
        return isset($this->options[$code]);
    }

    /**
     * {@inheritdoc}
     */
    public function is($code, $value = true)
    {
        return $this->get($code) === null ? false : $this->get($code) == $value;
    }

    /**
     * {@inheritdoc}
     */
    public function all(\Closure $filter = null)
    {
        if ($filter) {
            return array_filter($this->options, $filter);
        }

        return $this->options;
    }

    /**
     * {@inheritdoc}
     */
    public function merge($data, $override = true)
    {
        foreach ($this->convertToArray($data) as $code => $value) {
            if ($override || !$this->has($code)) {
                $this->set($code, $value);
            }
        }
    }

    /**
     * Converts $data to array
     *
     * @param MetadataInterface|array $data
     * @return array
     * @throws InvalidArgumentException
     */
    protected function convertToArray($data)
    {
        if ($data instanceof MetadataInterface) {
            $data = $data->all();
        } elseif (!is_array($data)) {
            throw new InvalidArgumentException(
                sprintf(
                    '$data must be instance of "%s" or array, "%s" given',
                    'Oro\\Bundle\\EntityMergeBundle\\Metadata\\MetadataInterface',
                    is_object($data) ? get_class($data) : gettype($data)
                )
            );
        }
        return $data;
    }
}
