<?php

namespace Oro\Bundle\AddressBundle\Entity\Manager;

use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;

/**
 * The API manager for Region entity.
 */
class RegionApiEntityManager extends ApiEntityManager
{
    /**
     * {@inheritdoc}
     */
    protected function getSerializationConfig()
    {
        return [
            'exclusion_policy' => 'all',
            'fields'           => [
                'combinedCode' => null,
                'code'         => null,
                'name'         => null,
                'country'      => ['property_path' => 'country.iso2Code'],
                '_country'      => ['property_path' => 'country', 'exclude' => true]
            ]
        ];
    }
}
