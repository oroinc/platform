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
}
