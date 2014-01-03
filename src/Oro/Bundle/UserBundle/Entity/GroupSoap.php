<?php

namespace Oro\Bundle\UserBundle\Entity;

use BeSimple\SoapBundle\ServiceDefinition\Annotation as Soap;

use Oro\Bundle\SoapBundle\Entity\SoapEntityInterface;

/**
 * @Soap\Alias("Oro.Bundle.UserBundle.Entity.Group")
 */
class GroupSoap extends Group implements SoapEntityInterface
{
    /**
     * @Soap\ComplexType("int", nillable=true)
     */
    protected $id;

    /**
     * @Soap\ComplexType("string")
     */
    protected $name;

    /**
     * @Soap\ComplexType("int", nillable=true)
     */
    protected $owner;

    /**
     * @param Group $group
     */
    public function soapInit($group)
    {
        $this->id = $group->id;
        $this->name = $group->name;
        $this->owner = $group->owner ? $group->owner->getId() : null;
    }
}
