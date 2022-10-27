<?php

namespace Oro\Bundle\GaufretteBundle\Stream;

use Gaufrette\Stream;
use Gaufrette\StreamMode;

/**
 * Provides read-only access to a resource that is stored in Gaufrette file system.
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ReadonlyResourceStream implements Stream
{
    /** @var resource */
    private $resource;

    public function __construct($resource)
    {
        $this->resource = $resource;
    }

    /**
     * {@inheritdoc}
     */
    public function open(StreamMode $mode)
    {
        if ($mode->allowsWrite()) {
            throw new \LogicException('The ReadonlyResourceStream does not allow write.');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function read($count)
    {
        return fread($this->resource, $count);
    }

    /**
     * {@inheritdoc}
     */
    public function write($data)
    {
        throw new \LogicException('The ReadonlyResourceStream does not allow write.');
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        fclose($this->resource);
    }

    /**
     * {@inheritdoc}
     */
    public function flush()
    {
        throw new \LogicException('The ReadonlyResourceStream does not allow write.');
    }

    /**
     * {@inheritdoc}
     */
    public function seek($offset, $whence = SEEK_SET)
    {
        return 0 === fseek($this->resource, $offset, $whence);
    }

    /**
     * {@inheritdoc}
     */
    public function tell()
    {
        return ftell($this->resource);
    }

    /**
     * {@inheritdoc}
     */
    public function eof()
    {
        return feof($this->resource);
    }

    /**
     * {@inheritdoc}
     */
    public function stat()
    {
        return fstat($this->resource);
    }

    /**
     * {@inheritdoc}
     */
    public function cast($castAs)
    {
        return $this->resource;
    }

    /**
     * {@inheritdoc}
     */
    public function unlink()
    {
        throw new \LogicException('The ReadonlyResourceStream does not allow unlink.');
    }
}
