<?php

namespace Oro\Bundle\ApiBundle\Model;

/**
 * Represents a reference to an error source.
 */
class ErrorSource
{
    /** @var string */
    protected $propertyPath;

    /** @var string */
    protected $pointer;

    /** @var string */
    protected $parameter;

    /**
     * Gets the path to a property caused the error.
     * e.g. "title", or "author.name"
     *
     * @return string|null
     */
    public function getPropertyPath()
    {
        return $this->propertyPath;
    }

    /**
     * Sets the path to a property caused the error.
     * e.g. "title", or "author.name"
     *
     * @param string|null $propertyPath
     */
    public function setPropertyPath($propertyPath)
    {
        $this->propertyPath = $propertyPath;
    }

    /**
     * Gets a pointer to a property in the request document caused the error.
     * For JSON documents the pointer conforms RFC 6901.
     * @see https://tools.ietf.org/html/rfc6901
     * e.g. "/data" for a primary data object, or "/data/attributes/title" for a specific attribute
     *
     * @return string|null
     */
    public function getPointer()
    {
        return $this->pointer;
    }

    /**
     * Sets a pointer to a property in the request document caused the error.
     * For JSON documents the pointer must conform RFC 6901.
     * @see https://tools.ietf.org/html/rfc6901
     * e.g. "/data" for a primary data object, or "/data/attributes/title" for a specific attribute
     *
     * @param string|null $pointer
     */
    public function setPointer($pointer)
    {
        $this->pointer = $pointer;
    }

    /**
     * Gets URI query parameter caused the error.
     *
     * @return string|null
     */
    public function getParameter()
    {
        return $this->parameter;
    }

    /**
     * Sets URI query parameter caused the error.
     *
     * @param string|null $parameter
     */
    public function setParameter($parameter)
    {
        $this->parameter = $parameter;
    }
}
