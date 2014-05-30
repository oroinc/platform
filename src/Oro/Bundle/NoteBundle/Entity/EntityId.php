<?php

namespace Oro\Bundle\NoteBundle\Entity;

use BeSimple\SoapBundle\ServiceDefinition\Annotation as Soap;

class EntityId
{
    /**
     * @Soap\ComplexType("string", nillable=false)
     */
    protected $entity;

    /**
     * @Soap\ComplexType("int", nillable=false)
     */
    protected $id;

    /**
     * @param mixed $entity
     *
     * @return EntityId
     */
    public function setEntity($entity)
    {
        $this->entity = $entity;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @param mixed $id
     *
     * @return EntityId
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }
}
