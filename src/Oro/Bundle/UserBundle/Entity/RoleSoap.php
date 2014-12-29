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
     * @Soap\ComplexType("string", nillable=true)
     */
    protected $role;

    /**
     * @Soap\ComplexType("string")
     */
    protected $label;


    /**
     * @param Role $role
     */
    public function soapInit($role)
    {
        $this->id = $role->id;
        $this->role = $role->role;
        $this->label = $role->label;
    }
}
