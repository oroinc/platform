<?php

namespace Oro\Bundle\OrganizationBundle\Entity;

use BeSimple\SoapBundle\ServiceDefinition\Annotation as Soap;

use Oro\Bundle\SoapBundle\Entity\SoapEntityInterface;

/**
 * @Soap\Alias("Oro.Bundle.OrganizationBundle.Entity.Organization")
 */
class OrganizationSoap extends Organization implements SoapEntityInterface
{
    /**
     * @Soap\ComplexType("int", nillable=true)
     */
    protected $id;

    /**
     * @Soap\ComplexType("string", nillable=true)
     */
    protected $name;

    /**
     * @Soap\ComplexType("string", nillable=true)
     */
    protected $currency;

    /**
     * @Soap\ComplexType("string", nillable=true)
     */
    protected $precision;

    /**
     * @param Organization $organization
     */
    public function soapInit($organization)
    {
        $this->id = $organization->id;
        $this->name = $organization->name;
        $this->currency = $organization->currency;
        $this->precision = $organization->precision;
    }
}
