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
     * @var DoctrineMetadata
     */
    protected $doctrineMetadata;

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
     * @return DoctrineMetadata
     */
    public function getDoctrineMetadata()
    {
        if (!$this->doctrineMetadata) {
            throw new InvalidArgumentException('DoctrineMetadata not set');
        }

        return $this->doctrineMetadata;
    }

    /**
     * @param array DoctrineMetadata
     */
    public function setDoctrineMetadata($doctrineMetadata)
    {
        $this->doctrineMetadata = $doctrineMetadata;
    }
}
