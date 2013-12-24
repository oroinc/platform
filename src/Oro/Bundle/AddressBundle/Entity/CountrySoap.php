<?php

namespace Oro\Bundle\AddressBundle\Entity;

use BeSimple\SoapBundle\ServiceDefinition\Annotation as Soap;

use Oro\Bundle\SoapBundle\Entity\SoapEntityInterface;

/**
 * @Soap\Alias("Oro.Bundle.AddressBundle.Entity.Country")
 */
class CountrySoap extends Country implements SoapEntityInterface
{
    /**
     * @Soap\ComplexType("string", nillable=true)
     */
    protected $iso2Code;

    /**
     * @Soap\ComplexType("string", nillable=true)
     */
    protected $iso3Code;

    /**
     * @Soap\ComplexType("string", nillable=true)
     */
    protected $name;

    /**
     * @param Country $country
     */
    public function soapInit($country)
    {
        $this->iso2Code = $country->iso2Code;
        $this->iso3Code = $country->iso3Code;
        $this->name = $country->name;
    }
}
