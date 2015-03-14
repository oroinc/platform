<?php

namespace Oro\Component\PropertyAccess;

class PropertyPath implements PropertyPathInterface
{
    /** @var string */
    protected $path;

    /** @var array */
    protected $elements;

    /**
     * @param string $path
     *
     * @throws Exception\InvalidPropertyPathException if a property path is invalid
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function __construct($path)
    {
        if (!is_string($path)) {
            throw new Exception\InvalidPropertyPathException(
                sprintf(
                    'Expected argument of type "string", "%s" given.',
                    is_object($path) ? get_class($path) : gettype($path)
                )
            );
        }
        if ($path === '') {
            throw new Exception\InvalidPropertyPathException('The property path must not be empty.');
        }

        $this->path     = $path;
        $this->elements = [];

        $remaining = $this->path;
        $pos       = 0;

        // first element is evaluated differently - no leading dot for properties
        if (preg_match('/^(([^\.\[]+)|\[([^\]]+)\])(.*)/', $remaining, $matches)) {
            $this->elements[] = $matches[2] === '' ? $matches[3] : $matches[2];

            $pos       = strlen($matches[1]);
            $remaining = $matches[4];
            $pattern   = '/^(\.([^\.|\[]+)|\[([^\]]+)\])(.*)/';
            while (preg_match($pattern, $remaining, $matches)) {
                $this->elements[] = $matches[2] === '' ? $matches[3] : $matches[2];

                $pos += strlen($matches[1]);
                $remaining = $matches[4];
            }
        }

        if ($remaining !== '') {
            $this->elements = null;
            throw new Exception\InvalidPropertyPathException(
                sprintf(
                    'Could not parse property path "%s". Unexpected token "%s" at position %d.',
                    $this->path,
                    $remaining[0],
                    $pos
                )
            );
        }
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
        return $this->elements;
    }
}
