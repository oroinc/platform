<?php

namespace Oro\Bundle\SearchBundle\Soap\Type;

use BeSimple\SoapBundle\ServiceDefinition\Annotation as Soap;
use BeSimple\SoapCommon\Type\AbstractKeyValue;

class SelectedValue extends AbstractKeyValue
{
    /**
     * @Soap\ComplexType("string")
     */
    protected $key;

    /**
     * @var string
     * @Soap\ComplexType("string")
     */
    protected $value;
}
