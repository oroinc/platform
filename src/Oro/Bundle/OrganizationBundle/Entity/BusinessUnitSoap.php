<?php

namespace Oro\Bundle\OrganizationBundle\Entity;

use BeSimple\SoapBundle\ServiceDefinition\Annotation as Soap;

use Oro\Bundle\SoapBundle\Entity\SoapEntityInterface;

/**
 * @Soap\Alias("Oro.Bundle.OrganizationBundle.Entity.BusinessUnit")
 */
class BusinessUnitSoap extends BusinessUnit implements SoapEntityInterface
{
    /**
     * @Soap\ComplexType("int", nillable=true)
     */
    protected $id;

    /**
     * @Soap\ComplexType("string", nillable=false)
     */
    protected $name;

    /**
     * @Soap\ComplexType("int", nillable=false)
     */
    protected $organization;

    /**
     * @Soap\ComplexType("string", nillable=true)
     */
    protected $phone;

    /**
     * @Soap\ComplexType("string", nillable=true)
     */
    protected $website;

    /**
     * @Soap\ComplexType("string", nillable=true)
     */
    protected $email;

    /**
     * @Soap\ComplexType("string", nillable=true)
     */
    protected $fax;

    /**
     * @Soap\ComplexType("dateTime", nillable=true)
     */
    protected $createdAt;

    /**
     * @Soap\ComplexType("dateTime", nillable=true)
     */
    protected $updatedAt;

    /**
     * @Soap\ComplexType("int", nillable=true)
     */
    protected $owner;

    /**
     * @param BusinessUnit $businessUnit
     */
    public function soapInit($businessUnit)
    {
        $this->id = $businessUnit->id;
        $this->name = $businessUnit->name;
        $this->organization = $businessUnit->organization ? $businessUnit->organization->getId() : null;
        $this->phone = $businessUnit->phone;
        $this->website = $businessUnit->website;
        $this->email = $businessUnit->email;
        $this->fax = $businessUnit->fax;
        $this->createdAt = $businessUnit->createdAt;
        $this->updatedAt = $businessUnit->updatedAt;
        $this->owner = $businessUnit->owner ? $businessUnit->owner->getId() : null;
    }
}
