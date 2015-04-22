<?php

namespace Oro\Component\ConfigExpression;

use Oro\Component\PropertyAccess\PropertyPathInterface;

final class CompiledPropertyPath implements PropertyPathInterface
{
    /** @var string */
    private $path;

    /** @var array */
    private $elements;

    /**
     * @param string $path
     * @param array  $elements
     */
    public function __construct($path, array $elements)
    {
        $this->path     = $path;
        $this->elements = $elements;
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
