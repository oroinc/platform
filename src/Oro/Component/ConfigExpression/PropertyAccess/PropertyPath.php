<?php

namespace Oro\Component\ConfigExpression\PropertyAccess;

use Oro\Component\ConfigExpression\Exception;

class PropertyPath implements PropertyPathInterface
{
    /** @var string */
    protected $path;

    /** @var array */
    protected $elements;

    /**
     * @param string $path
     */
    public function __construct($path)
    {
        if (!is_string($path)) {
            throw new Exception\UnexpectedTypeException($path, 'string');
        }
        if ($path === '') {
            throw new Exception\InvalidArgumentException('The property path must not be empty.');
        }

        $this->path = $path;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return $this->path;
    }

    /**
     * {@inheritdoc}
     */
    public function getElements()
    {
        if ($this->elements === null) {
            $this->elements = explode('.', $this->path);
        }

        return $this->elements;
    }
}
