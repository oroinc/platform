<?php

namespace Oro\Bundle\ApiBundle\Model;

/**
 * Represents a reference to an error source.
 */
final class ErrorSource
{
    private ?string $propertyPath = null;
    private ?string $pointer = null;
    private ?string $parameter = null;

    /**
     * Creates an instance of ErrorSource class represents
     * the path to a property caused the error.
     *
     * @param string $propertyPath The property path.
     *                             If it contains several elements they should be separated by the point (.)
     *
     * @return $this
     */
    public static function createByPropertyPath(string $propertyPath): self
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
     *                        If it contains several elements they should be separated by the slash (/),
     *                        e.g. "/data" for a primary data object,
     *                        or "/data/attributes/title" for a specific attribute
     *
     * @return $this
     */
    public static function createByPointer(string $pointer): self
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
     * @return $this
     */
    public static function createByParameter(string $parameter): self
    {
        $source = new self();
        $source->setParameter($parameter);

        return $source;
    }

    /**
     * Gets the path to a property caused the error.
     * e.g. "title", or "author.name"
     */
    public function getPropertyPath(): ?string
    {
        return $this->propertyPath;
    }

    /**
     * Sets the path to a property caused the error.
     * e.g. "title", or "author.name"
     */
    public function setPropertyPath(?string $propertyPath): void
    {
        $this->propertyPath = $propertyPath;
    }

    /**
     * Gets a pointer to a property in the request document caused the error.
     * For JSON documents the pointer conforms RFC 6901.
     * @link https://tools.ietf.org/html/rfc6901
     * e.g. "/data" for a primary data object, or "/data/attributes/title" for a specific attribute
     */
    public function getPointer(): ?string
    {
        return $this->pointer;
    }

    /**
     * Sets a pointer to a property in the request document caused the error.
     * For JSON documents the pointer must conform RFC 6901.
     * @link https://tools.ietf.org/html/rfc6901
     * e.g. "/data" for a primary data object, or "/data/attributes/title" for a specific attribute
     */
    public function setPointer(?string $pointer): void
    {
        $this->pointer = $pointer;
    }

    /**
     * Gets URI query parameter caused the error.
     */
    public function getParameter(): ?string
    {
        return $this->parameter;
    }

    /**
     * Sets URI query parameter caused the error.
     */
    public function setParameter(?string $parameter): void
    {
        $this->parameter = $parameter;
    }
}
