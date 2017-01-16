<?php

namespace Oro\Bundle\ApiBundle\Model;

/**
 * This class can be used if you need a Data API sub-resource that should return
 * a list of objects contain a reference to different types of entities
 * and its human-readable representation.
 * @deprecated since 2.0. Use Oro\Bundle\ApiBundle\Model\EntityIdentifier instead
 */
class EntityDescriptor extends EntityIdentifier
{
    /** @var string */
    protected $title;

    /**
     * @param mixed|null  $id
     * @param string|null $class
     * @param string|null $title
     */
    public function __construct($id = null, $class = null, $title = null)
    {
        parent::__construct($id, $class);
        $this->title = $title;
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
