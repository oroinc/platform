<?php

namespace Oro\Bundle\AddressBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;

/**
 * Tests for address country and region consistency.
 * This trait requires the following constants:
 * * ENTITY_CLASS
 * * ENTITY_TYPE
 * * COUNTRY_REGION_ADDRESS_REF
 * * IS_REGION_REQUIRED
 */
trait AddressCountryAndRegionTestTrait
{
    private function resetAddressCountryAndRegion()
    {
        /** @var AbstractAddress $address */
        $address = $this->getReference(self::COUNTRY_REGION_ADDRESS_REF);
        $address->setCountry($this->getEntityManager()->find(Country::class, 'US'));
        $address->setRegion($this->getEntityManager()->find(Region::class, 'US-NY'));
        $address->setRegionText(null);
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();
    }

    public function testTryToSetCountryIncompatibleWithExistingRegion()
    {
        $this->resetAddressCountryAndRegion();
        $countryId = $this->getEntityManager()->find(Country::class, 'MX')->getIso2Code();
        $addressId = $this->getReference(self::COUNTRY_REGION_ADDRESS_REF)->getId();
        $data = [
            'data' => [
                'type'          => self::ENTITY_TYPE,
                'id'            => (string)$addressId,
                'relationships' => [
                    'country' => [
                        'data' => [
                            'type' => 'countries',
                            'id'   => $countryId
                        ]
                    ]
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => self::ENTITY_TYPE, 'id' => $addressId],
            $data,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'valid region constraint',
                'detail' => 'Region New York does not belong to country Mexico'
            ],
            $response
        );
    }

    public function testTryToSetRegionIncompatibleWithExistingCountry()
    {
        $this->resetAddressCountryAndRegion();
        $regionId = $this->getEntityManager()->find(Region::class, 'MX-GUA')->getCombinedCode();
        $addressId = $this->getReference(self::COUNTRY_REGION_ADDRESS_REF)->getId();
        $data = [
            'data' => [
                'type'          => self::ENTITY_TYPE,
                'id'            => (string)$addressId,
                'relationships' => [
                    'region' => [
                        'data' => [
                            'type' => 'regions',
                            'id'   => $regionId
                        ]
                    ]
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => self::ENTITY_TYPE, 'id' => $addressId],
            $data,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'valid region constraint',
                'detail' => 'Region Guanajuato does not belong to country United States'
            ],
            $response
        );
    }

    public function testTryToSetIncompatibleCountryAndRegion()
    {
        $this->resetAddressCountryAndRegion();
        $countryId = $this->getEntityManager()->find(Country::class, 'GB')->getIso2Code();
        $regionId = $this->getEntityManager()->find(Region::class, 'US-NY')->getCombinedCode();
        $addressId = $this->getReference(self::COUNTRY_REGION_ADDRESS_REF)->getId();
        $data = [
            'data' => [
                'type'          => self::ENTITY_TYPE,
                'id'            => (string)$addressId,
                'relationships' => [
                    'country' => [
                        'data' => [
                            'type' => 'countries',
                            'id'   => $countryId
                        ]
                    ],
                    'region'  => [
                        'data' => [
                            'type' => 'regions',
                            'id'   => $regionId
                        ]
                    ]
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => self::ENTITY_TYPE, 'id' => $addressId],
            $data,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'valid region constraint',
                'detail' => 'Region New York does not belong to country United Kingdom'
            ],
            $response
        );
    }

    public function testSetNullRegionForCountryThatDoesNotHaveRegions()
    {
        $this->resetAddressCountryAndRegion();
        $countryId = $this->getEntityManager()->find(Country::class, 'IM')->getIso2Code();
        $addressId = $this->getReference(self::COUNTRY_REGION_ADDRESS_REF)->getId();
        $data = [
            'data' => [
                'type'          => self::ENTITY_TYPE,
                'id'            => (string)$addressId,
                'relationships' => [
                    'country' => [
                        'data' => [
                            'type' => 'countries',
                            'id'   => $countryId
                        ]
                    ],
                    'region'  => [
                        'data' => null
                    ]
                ]
            ]
        ];
        $this->patch(
            ['entity' => self::ENTITY_TYPE, 'id' => $addressId],
            $data
        );

        /** @var AbstractAddress $address */
        $address = $this->getEntityManager()
            ->find(self::ENTITY_CLASS, $addressId);
        self::assertEquals($countryId, $address->getCountry()->getIso2Code());
        self::assertTrue(null === $address->getRegion());
    }

    public function testTryToSetNullRegionForCountryThatHasRegions()
    {
        $this->resetAddressCountryAndRegion();
        $addressId = $this->getReference(self::COUNTRY_REGION_ADDRESS_REF)->getId();
        $data = [
            'data' => [
                'type'          => self::ENTITY_TYPE,
                'id'            => (string)$addressId,
                'relationships' => [
                    'region' => [
                        'data' => null
                    ]
                ]
            ]
        ];

        if (self::IS_REGION_REQUIRED) {
            $response = $this->patch(
                ['entity' => self::ENTITY_TYPE, 'id' => $addressId],
                $data,
                [],
                false
            );
            $this->assertResponseValidationError(
                [
                    'title'  => 'required region constraint',
                    'detail' => 'State is required for country United States'
                ],
                $response
            );
        } else {
            $this->patch(
                ['entity' => self::ENTITY_TYPE, 'id' => $addressId],
                $data
            );
            /** @var AbstractAddress $address */
            $address = $this->getEntityManager()
                ->find(self::ENTITY_CLASS, $addressId);
            self::assertTrue(null === $address->getRegion());
        }
    }

    public function testSetCustomRegionForCountryThatDoesNotHaveRegions()
    {
        $this->resetAddressCountryAndRegion();
        $countryId = $this->getEntityManager()->find(Country::class, 'IM')->getIso2Code();
        $addressId = $this->getReference(self::COUNTRY_REGION_ADDRESS_REF)->getId();
        $data = [
            'data' => [
                'type'          => self::ENTITY_TYPE,
                'id'            => (string)$addressId,
                'attributes'    => [
                    'customRegion' => 'some region'
                ],
                'relationships' => [
                    'country' => [
                        'data' => [
                            'type' => 'countries',
                            'id'   => $countryId
                        ]
                    ],
                    'region'  => [
                        'data' => null
                    ]
                ]
            ]
        ];
        $this->patch(
            ['entity' => self::ENTITY_TYPE, 'id' => $addressId],
            $data
        );

        /** @var AbstractAddress $address */
        $address = $this->getEntityManager()
            ->find(self::ENTITY_CLASS, $addressId);
        self::assertEquals($countryId, $address->getCountry()->getIso2Code());
        self::assertTrue(null === $address->getRegion());
        self::assertEquals('some region', $address->getRegionText());
    }

    public function testTryToSetCustomRegionForCountryThatHasRegions()
    {
        $this->resetAddressCountryAndRegion();
        $addressId = $this->getReference(self::COUNTRY_REGION_ADDRESS_REF)->getId();
        $data = [
            'data' => [
                'type'          => self::ENTITY_TYPE,
                'id'            => (string)$addressId,
                'attributes'    => [
                    'customRegion' => 'some region'
                ],
                'relationships' => [
                    'region' => [
                        'data' => null
                    ]
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => self::ENTITY_TYPE, 'id' => $addressId],
            $data,
            [],
            false
        );

        if (self::IS_REGION_REQUIRED) {
            $this->assertResponseValidationErrors(
                [
                    [
                        'title'  => 'region text constraint',
                        'detail' => 'Custom region can be used only for countries without predefined regions.'
                    ],
                    [
                        'title'  => 'required region constraint',
                        'detail' => 'State is required for country United States',
                        'source' => ['pointer' => '/data/relationships/region/data']
                    ]
                ],
                $response
            );
        } else {
            $this->assertResponseValidationError(
                [
                    'title'  => 'region text constraint',
                    'detail' => 'Custom region can be used only for countries without predefined regions.'
                ],
                $response
            );
        }
    }
}
