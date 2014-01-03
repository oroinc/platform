<?php

namespace Oro\Bundle\AddressBundle\Entity;

use BeSimple\SoapBundle\ServiceDefinition\Annotation as Soap;

use Oro\Bundle\SoapBundle\Entity\SoapEntityInterface;

/**
 * @Soap\Alias("Oro.Bundle.AddressBundle.Entity.AddressType")
 */
class AddressTypeSoap extends AddressType implements SoapEntityInterface
{
    /**
     * @Soap\ComplexType("string", nillable=true)
     */
    protected $name;

    /**
     * @Soap\ComplexType("string", nillable=true)
     */
    protected $label;

    /**
     * @param AddressType $addressType
     */
    public function soapInit($addressType)
    {
        $this->name = $addressType->name;
        $this->label = $addressType->label;
    }
}
