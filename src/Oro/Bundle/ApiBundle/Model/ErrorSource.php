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
     * Creates an instance of ErrorSource class represents
     * the path to a property caused the error.
     *
     * @param string $propertyPath The property path.
     *                             If it contains several elements they should be separated by the point (.)
     *
     * @return ErrorSource
     */
    public static function createByPropertyPath($propertyPath)
    {
        $source = new self();
        $source->setPropertyPath($propertyPath);

        return $source;
    }

    /**
     * Creates an instance of ErrorSource class represents
     * a pointer to a property in the request document caused the error.
     *
     * @param string $pointer The property pointer.
     *                        If it contains several elements they should be separated by the slash (/)
     *
     * @return ErrorSource
     */
    public static function createByPointer($pointer)
    {
        $source = new self();
        $source->setPointer($pointer);

        return $source;
    }

    /**
     * Creates an instance of ErrorSource class represents
     * URI query parameter caused the error.
     *
     * @param string $parameter The name of a parameter.
     *
     * @return ErrorSource
     */
    public static function createByParameter($parameter)
    {
        $source = new self();
        $source->setParameter($parameter);

        return $source;
    }

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
