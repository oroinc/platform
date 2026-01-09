<?php

namespace Oro\Component\Duplicator;

/**
 * Represents a type of object with its associated keyword and class name.
 *
 * This class encapsulates the metadata for an object type, including the
 * keyword identifier and the fully qualified class name it represents.
 */
class ObjectType
{
    /**
     * @var string
     */
    protected $className;

    /**
     * @var string
     */
    protected $keyword;

    /**
     * @param string $keyword
     * @param string $className
     */
    public function __construct($keyword, $className)
    {
        $this->keyword = $keyword;
        $this->className = $className;
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * @return string
     */
    public function getKeyword()
    {
        return $this->keyword;
    }
}
