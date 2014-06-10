<?php

namespace Oro\Bundle\EntityBundle\Model;

use BeSimple\SoapBundle\ServiceDefinition\Annotation as Soap;

/**
 * @Soap\Alias("Oro.Bundle.EntityBundle.Entity.EntityId")
 */
class EntityIdSoap
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
     * @return EntityIdSoap
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
     * @return EntityIdSoap
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
