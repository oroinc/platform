<?php

namespace Oro\Bundle\AddressBundle\Entity\Manager;

use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;

/**
 * The API manager for Country entity.
 */
class CountryApiEntityManager extends ApiEntityManager
{
    /**
     * {@inheritdoc}
     */
    protected function getSerializationConfig()
    {
        return [
            'exclusion_policy' => 'all',
            'fields'           => [
                'iso2code' => ['property_path' => 'iso2Code'],
                'iso3code' => ['property_path' => 'iso3Code'],
                'name'     => null
            ]
        ];
    }
}
