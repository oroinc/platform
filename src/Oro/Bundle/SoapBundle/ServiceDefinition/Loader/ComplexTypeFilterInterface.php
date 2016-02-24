<?php

namespace Oro\Bundle\SoapBundle\ServiceDefinition\Loader;

use BeSimple\SoapBundle\Util\Collection;

interface ComplexTypeFilterInterface
{
    /**
     * Filter object properties for SOAP (wsdl)
     *
     * @param  string    $className
     * @param Collection $properties
     *
     * @return Collection Filtered collection
     */
    public function filterProperties($className, Collection $properties);
}
