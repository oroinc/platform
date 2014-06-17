<?php

namespace Oro\Bundle\OrganizationBundle\Autocomplete;

use Oro\Bundle\FormBundle\Autocomplete\SearchHandler;

class OrganizationSearchHandler extends SearchHandler
{
    /**
     * @param string $entityName
     * @param array $properties
     */
    public function __construct($entityName, array $properties)
    {
        parent::__construct($entityName, $properties);
    }
}
