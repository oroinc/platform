<?php

namespace Oro\Bundle\SoapBundle\ServiceDefinition\Loader;

interface FilterableLoaderInterface
{
    /**
     * @param ComplexTypeFilterInterface $filter
     *
     * @return void
     */
    public function addTypeFilter(ComplexTypeFilterInterface $filter);
}
