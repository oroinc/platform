<?php

namespace Oro\Bundle\SoapBundle\Type\KeyValue;

use BeSimple\SoapBundle\ServiceDefinition\Annotation as Soap;
use BeSimple\SoapCommon\Type\AbstractKeyValue;

class StringType extends AbstractKeyValue
{
    /**
     * @Soap\ComplexType("string", nillable=true)
     */
    protected $value;
}
