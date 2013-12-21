<?php

namespace Oro\Bundle\UserBundle\Entity;

use BeSimple\SoapBundle\ServiceDefinition\Annotation as Soap;

use Oro\Bundle\SoapBundle\Entity\SoapEntityInterface;

/**
 * @Soap\Alias("Oro.Bundle.UserBundle.Entity.Role")
 */
class RoleSoap extends Role implements SoapEntityInterface
{
    /**
     * @Soap\ComplexType("int", nillable=true)
     */
    protected $id;

    /**
     * @Soap\ComplexType("string")
     */
    protected $role;

    /**
     * @Soap\ComplexType("string")
     */
    protected $label;

    /**
     * @Soap\ComplexType("int", nillable=true)
     */
    protected $owner;

    /**
     * @param Role $role
     */
    public function soapInit($role)
    {
        $this->id = $role->id;
        $this->role = $role->role;
        $this->label = $role->label;
        $this->owner = $role->owner ? $role->owner->getId() : null;
    }
}
