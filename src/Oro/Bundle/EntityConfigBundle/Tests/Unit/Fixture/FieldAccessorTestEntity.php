<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Fixture;

use Doctrine\Common\Collections\ArrayCollection;

class FieldAccessorTestEntity
{
    private $name;

    private $default_name;

    private $anotherName;

    /** @var ArrayCollection */
    private $related_entity;

    /** @var ArrayCollection */
    private $anotherRelatedEntity;

    public function __construct()
    {
        $this->related_entity       = new ArrayCollection();
        $this->anotherRelatedEntity = new ArrayCollection();
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function getDefaultName()
    {
        return $this->default_name;
    }

    public function setDefaultName($name)
    {
        $this->default_name = $name;

        return $this;
    }

    public function getAnotherName()
    {
        return $this->anotherName;
    }

    public function setAnotherName($name)
    {
        $this->anotherName = $name;

        return $this;
    }

    public function getRelatedEntities()
    {
        return $this->related_entity;
    }

    public function addRelatedEntity($entity)
    {
        $this->related_entity->add($entity);

        return $this;
    }

    public function removeRelatedEntity($entity)
    {
        $this->related_entity->removeElement($entity);

        return $this;
    }

    public function getAnotherRelatedEntities()
    {
        return $this->anotherRelatedEntity;
    }

    public function addAnotherRelatedEntity($entity)
    {
        $this->anotherRelatedEntity->add($entity);

        return $this;
    }

    public function removeAnotherRelatedEntity($entity)
    {
        $this->anotherRelatedEntity->removeElement($entity);

        return $this;
    }
}
