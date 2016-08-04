<?php

namespace Oro\Bundle\ApiBundle\Model;

/**
 * This class can be used if you need a Data API sub-resource that should return
 * a list of objects contain a reference to different types of entities
 * and its human-readable representation.
 */
class EntityDescriptor extends EntityIdentifier
{
    /** @var string */
    protected $class;

    /** @var string */
    protected $title;

    /**
     * @param mixed|null  $id
     * @param string|null $class
     * @param string|null $title
     */
    public function __construct($id = null, $class = null, $title = null)
    {
        parent::__construct($id);
        $this->class = $class;
        $this->title = $title;
    }

    /**
     * Gets the FQCN of the entity.
     *
     * @return mixed
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * Sets the FQCN of the entity.
     *
     * @param string $class
     */
    public function setClass($class)
    {
        $this->class = $class;
    }

    /**
     * Gets a short, human-readable representation of the entity.
     *
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Sets a short, human-readable representation of the entity.
     *
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }
}
