<?php

namespace Oro\Bundle\AddressBundle\Entity;

use BeSimple\SoapBundle\ServiceDefinition\Annotation as Soap;

use Oro\Bundle\SoapBundle\Entity\SoapEntityInterface;

/**
 * @Soap\Alias("Oro.Bundle.AddressBundle.Entity.Region")
 */
class RegionSoap extends Region implements SoapEntityInterface
{
    /**
     * @Soap\ComplexType("string", nillable=true)
     */
    protected $combinedCode;

    /**
     * @Soap\ComplexType("string", nillable=true)
     */
    protected $country;

    /**
     * @Soap\ComplexType("string", nillable=true)
     */
    protected $code;

    /**
     * @Soap\ComplexType("string", nillable=true)
     */
    protected $name;

    /**
     * @param Region $region
     */
    public function soapInit($region)
    {
        $this->combinedCode = $region->combinedCode;
        $this->country = $region->country ? $region->country->getIso2Code() : null;
        $this->code = $region->code;
        $this->name = $region->name;
    }
}
