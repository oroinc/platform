<?php

namespace Oro\Bundle\AddressBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\ApiBundle\Request\ApiActions;

/**
 * Tests to check that address owner is unchangeable.
 * This trait requires the following constants:
 * * ENTITY_CLASS
 * * ENTITY_TYPE
 * * OWNER_ENTITY_TYPE
 * * OWNER_RELATIONSHIP
 * * CREATE_MIN_REQUEST_DATA
 * * OWNER_CREATE_MIN_REQUEST_DATA (optional, can be omitted if the owner API resource is read-only)
 * * UNCHANGEABLE_ADDRESS_REF
 * * ANOTHER_OWNER_REF
 * * ANOTHER_OWNER_ADDRESS_2_REF
 * and the following methods:
 * * getOwner($address)
 */
trait UnchangeableAddressOwnerTestTrait
{
    public function testCreateViaOwnerCreateResourceAndAddressDoesNotHaveOwnerRelationship()
    {
        if (!$this->isActionEnabled($this->getEntityClass(self::OWNER_ENTITY_TYPE), ApiActions::CREATE)) {
            self::markTestSkipped('The "create" action is disabled for owner entity');
        }

        $addressData = $this->getRequestData(self::CREATE_MIN_REQUEST_DATA);
        $addressData['data']['id'] = 'new_address';
        unset($addressData['data']['relationships'][self::OWNER_RELATIONSHIP]);
        $data = $this->getRequestData(self::OWNER_CREATE_MIN_REQUEST_DATA);
        $data['data']['relationships']['addresses']['data'] = [
            ['type' => self::ENTITY_TYPE, 'id' => 'new_address']
        ];
        $data['included'] = [
            $addressData['data']
        ];
        $response = $this->post(
            ['entity' => self::OWNER_ENTITY_TYPE],
            $data
        );

        $ownerId = (int)$this->getResourceId($response);
        $addressId = (int)self::getNewResourceIdFromIncludedSection($response, 'new_address');
        /** @var AbstractAddress $address */
        $address = $this->getEntityManager()
            ->find(self::ENTITY_CLASS, $addressId);
        /** @var object $owner */
        $owner = $this->getOwner($address);
        self::assertSame($ownerId, $owner->getId());
        self::assertCount(1, $owner->getAddresses());
        self::assertSame($addressId, $owner->getAddresses()->first()->getId());
    }

    public function testCreateViaOwnerCreateResourceAndAddressHasOwnerRelationship()
    {
        if (!$this->isActionEnabled($this->getEntityClass(self::OWNER_ENTITY_TYPE), ApiActions::CREATE)) {
            self::markTestSkipped('The "create" action is disabled for owner entity');
        }

        $addressData = $this->getRequestData(self::CREATE_MIN_REQUEST_DATA);
        $addressData['data']['id'] = 'new_address';
        $addressData['data']['relationships'][self::OWNER_RELATIONSHIP]['data'] = [
            'type' => self::OWNER_ENTITY_TYPE,
            'id'   => 'new_owner'
        ];
        $data = $this->getRequestData(self::OWNER_CREATE_MIN_REQUEST_DATA);
        $data['data']['id'] = 'new_owner';
        $data['included'] = [
            $addressData['data']
        ];
        $response = $this->post(
            ['entity' => self::OWNER_ENTITY_TYPE],
            $data
        );

        $ownerId = (int)$this->getResourceId($response);
        $addressId = (int)self::getNewResourceIdFromIncludedSection($response, 'new_address');
        /** @var AbstractAddress $address */
        $address = $this->getEntityManager()
            ->find(self::ENTITY_CLASS, $addressId);
        /** @var object $owner */
        $owner = $this->getOwner($address);
        self::assertSame($ownerId, $owner->getId());
        self::assertCount(1, $owner->getAddresses());
        self::assertSame($addressId, $owner->getAddresses()->first()->getId());
    }

    public function testCreateViaOwnerUpdateResourceAndAddressDoesNotHaveOwnerRelationship()
    {
        if (!$this->isActionEnabled($this->getEntityClass(self::OWNER_ENTITY_TYPE), ApiActions::UPDATE)) {
            self::markTestSkipped('The "update" action is disabled for owner entity');
        }

        $ownerId = $this->getReference(self::ANOTHER_OWNER_REF)->getId();

        $addressData = $this->getRequestData(self::CREATE_MIN_REQUEST_DATA);
        $addressData['data']['id'] = 'new_address';
        unset($addressData['data']['relationships'][self::OWNER_RELATIONSHIP]);
        $data = [
            'data'     => [
                'type'          => self::OWNER_ENTITY_TYPE,
                'id'            => (string)$ownerId,
                'relationships' => [
                    'addresses' => [
                        'data' => [
                            ['type' => self::ENTITY_TYPE, 'id' => 'new_address']
                        ]
                    ]
                ]
            ],
            'included' => [
                $addressData['data']
            ]
        ];
        $response = $this->patch(
            ['entity' => self::OWNER_ENTITY_TYPE, 'id' => (string)$ownerId],
            $data
        );

        $addressId = (int)self::getNewResourceIdFromIncludedSection($response, 'new_address');
        /** @var AbstractAddress $address */
        $address = $this->getEntityManager()
            ->find(self::ENTITY_CLASS, $addressId);
        /** @var object $owner */
        $owner = $this->getOwner($address);
        self::assertSame($ownerId, $owner->getId());
        self::assertCount(1, $owner->getAddresses());
        self::assertSame($addressId, $owner->getAddresses()->first()->getId());
    }

    public function testCreateViaOwnerUpdateResourceAndAddressHasOwnerRelationship()
    {
        if (!$this->isActionEnabled($this->getEntityClass(self::OWNER_ENTITY_TYPE), ApiActions::UPDATE)) {
            self::markTestSkipped('The "update" action is disabled for owner entity');
        }

        $ownerId = $this->getReference(self::ANOTHER_OWNER_REF)->getId();

        $addressData = $this->getRequestData(self::CREATE_MIN_REQUEST_DATA);
        $addressData['data']['id'] = 'new_address';
        $addressData['data']['relationships'][self::OWNER_RELATIONSHIP]['data'] = [
            'type' => self::OWNER_ENTITY_TYPE,
            'id'   => (string)$ownerId
        ];
        $data = [
            'data'     => [
                'type' => self::OWNER_ENTITY_TYPE,
                'id'   => (string)$ownerId
            ],
            'included' => [
                $addressData['data']
            ]
        ];
        $response = $this->patch(
            ['entity' => self::OWNER_ENTITY_TYPE, 'id' => (string)$ownerId],
            $data
        );

        $addressId = (int)self::getNewResourceIdFromIncludedSection($response, 'new_address');
        /** @var AbstractAddress $address */
        $address = $this->getEntityManager()
            ->find(self::ENTITY_CLASS, $addressId);
        /** @var object $owner */
        $owner = $this->getOwner($address);
        self::assertSame($ownerId, $owner->getId());
        self::assertCount(3, $owner->getAddresses());
        self::assertTrue(
            in_array(
                $addressId,
                array_map(
                    function (AbstractAddress $a) {
                        return $a->getId();
                    },
                    $owner->getAddresses()->toArray()
                ),
                true
            )
        );
    }

    public function testTryToChangeOwnerViaOwnerCreateResource()
    {
        if (!$this->isActionEnabled($this->getEntityClass(self::OWNER_ENTITY_TYPE), ApiActions::CREATE)) {
            self::markTestSkipped('The "create" action is disabled for owner entity');
        }

        $address1Id = $this->getReference(self::UNCHANGEABLE_ADDRESS_REF)->getId();
        $address2Id = $this->getReference(self::ANOTHER_OWNER_ADDRESS_2_REF)->getId();

        $addressData = $this->getRequestData(self::CREATE_MIN_REQUEST_DATA);
        $addressData['data']['id'] = 'new_address';
        unset($addressData['data']['relationships'][self::OWNER_RELATIONSHIP]);
        $data = $this->getRequestData(self::OWNER_CREATE_MIN_REQUEST_DATA);
        $data['data']['relationships']['addresses']['data'] = [
            ['type' => self::ENTITY_TYPE, 'id' => (string)$address1Id],
            ['type' => self::ENTITY_TYPE, 'id' => 'new_address'],
            ['type' => self::ENTITY_TYPE, 'id' => (string)$address2Id]
        ];
        $data['included'] = [
            $addressData['data']
        ];
        $response = $this->post(
            ['entity' => self::OWNER_ENTITY_TYPE],
            $data,
            [],
            false
        );

        $this->assertResponseValidationErrors(
            [
                [
                    'title'  => 'unchangeable field constraint',
                    'detail' => 'Address owner cannot be changed once set.',
                    'source' => ['pointer' => '/data/relationships/addresses/data/0']
                ],
                [
                    'title'  => 'unchangeable field constraint',
                    'detail' => 'Address owner cannot be changed once set.',
                    'source' => ['pointer' => '/data/relationships/addresses/data/2']
                ]
            ],
            $response
        );
    }

    public function testTryToChangeOwnerViaOwnerUpdateResource()
    {
        if (!$this->isActionEnabled($this->getEntityClass(self::OWNER_ENTITY_TYPE), ApiActions::UPDATE)) {
            self::markTestSkipped('The "update" action is disabled for owner entity');
        }

        /** @var AbstractAddress $address */
        $address = $this->getReference(self::UNCHANGEABLE_ADDRESS_REF);
        $ownerId = $this->getOwner($address)->getId();
        $anotherAddressId = $this->getReference(self::ANOTHER_OWNER_ADDRESS_2_REF)->getId();

        $addressesData = [];
        foreach ($this->getOwner($address)->getAddresses() as $addr) {
            $addressesData[] = ['type' => self::ENTITY_TYPE, 'id' => (string)$addr->getId()];
        }
        $addressesData = array_merge(
            [array_shift($addressesData), ['type' => self::ENTITY_TYPE, 'id' => (string)$anotherAddressId]],
            $addressesData
        );
        $data = [
            'data' => [
                'type'          => self::OWNER_ENTITY_TYPE,
                'id'            => (string)$ownerId,
                'relationships' => [
                    'addresses' => [
                        'data' => $addressesData
                    ]
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => self::OWNER_ENTITY_TYPE, 'id' => (string)$ownerId],
            $data,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'unchangeable field constraint',
                'detail' => 'Address owner cannot be changed once set.',
                'source' => ['pointer' => '/data/relationships/addresses/data/1']
            ],
            $response
        );
    }

    public function testRemoveAddressFromOwnerViaOwnerUpdateResource()
    {
        if (!$this->isActionEnabled($this->getEntityClass(self::OWNER_ENTITY_TYPE), ApiActions::UPDATE)) {
            self::markTestSkipped('The "update" action is disabled for owner entity');
        }

        /** @var AbstractAddress $address */
        $address = $this->getReference(self::UNCHANGEABLE_ADDRESS_REF);
        $addressId = $address->getId();
        $ownerId = $this->getOwner($address)->getId();

        $addressIdsToBeRemoved = [];
        foreach ($this->getOwner($address)->getAddresses() as $addr) {
            if ($addr->getId() !== $addressId) {
                $addressIdsToBeRemoved[] = $addr->getId();
            }
        }
        self::assertNotEmpty($addressIdsToBeRemoved);

        $data = [
            'data' => [
                'type'          => self::OWNER_ENTITY_TYPE,
                'id'            => (string)$ownerId,
                'relationships' => [
                    'addresses' => [
                        'data' => [
                            ['type' => self::ENTITY_TYPE, 'id' => (string)$addressId]
                        ]
                    ]
                ]
            ]
        ];
        $this->patch(
            ['entity' => self::OWNER_ENTITY_TYPE, 'id' => (string)$ownerId],
            $data
        );

        foreach ($addressIdsToBeRemoved as $id) {
            self::assertTrue(
                null === $this->getEntityManager()->find(self::ENTITY_CLASS, $id),
                sprintf('Address ID: %s', $id)
            );
        }
    }

    public function testTryToChangeOwnerViaOwnerUpdateResourceAndAddressHasOwnerRelationship()
    {
        if (!$this->isActionEnabled($this->getEntityClass(self::OWNER_ENTITY_TYPE), ApiActions::UPDATE)) {
            self::markTestSkipped('The "update" action is disabled for owner entity');
        }

        /** @var AbstractAddress $address */
        $address = $this->getReference(self::ANOTHER_OWNER_ADDRESS_2_REF);
        $addressId = $address->getId();
        /** @var object $newOwner */
        $newOwner = $this->getReference(self::ANOTHER_OWNER_REF);
        $newOwnerId = $newOwner->getId();

        $data = [
            'data'     => [
                'type' => self::OWNER_ENTITY_TYPE,
                'id'   => (string)$newOwnerId
            ],
            'included' => [
                [
                    'type'          => self::ENTITY_TYPE,
                    'id'            => (string)$addressId,
                    'meta'          => [
                        'update' => true
                    ],
                    'relationships' => [
                        self::OWNER_RELATIONSHIP => [
                            'data' => ['type' => self::OWNER_ENTITY_TYPE, 'id' => (string)$newOwnerId]
                        ]
                    ]
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => self::OWNER_ENTITY_TYPE, 'id' => (string)$newOwnerId],
            $data,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'extra fields constraint',
                'detail' => sprintf('This form should not contain extra fields: "%s"', self::OWNER_RELATIONSHIP),
                'source' => ['pointer' => '/included/0']
            ],
            $response
        );
    }

    public function testTryToChangeOwnerViaOwnerAddressesUpdateRelationshipResource()
    {
        /** @var AbstractAddress $address */
        $address = $this->getReference(self::UNCHANGEABLE_ADDRESS_REF);
        $addressId = $address->getId();
        $ownerId = $this->getOwner($address)->getId();
        $anotherAddressId = $this->getReference(self::ANOTHER_OWNER_ADDRESS_2_REF)->getId();

        $data = [
            'data' => [
                ['type' => self::ENTITY_TYPE, 'id' => (string)$addressId],
                ['type' => self::ENTITY_TYPE, 'id' => (string)$anotherAddressId]
            ]
        ];
        $response = $this->patchRelationship(
            ['entity' => self::OWNER_ENTITY_TYPE, 'id' => (string)$ownerId, 'association' => 'addresses'],
            $data,
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToChangeOwnerViaOwnerAddressesAddRelationshipResource()
    {
        /** @var AbstractAddress $address */
        $address = $this->getReference(self::ANOTHER_OWNER_ADDRESS_2_REF);
        $addressId = $address->getId();
        /** @var object $newOwner */
        $newOwner = $this->getReference(self::ANOTHER_OWNER_REF);
        $newOwnerId = $newOwner->getId();

        $data = [
            'data' => [
                ['type' => self::ENTITY_TYPE, 'id' => (string)$addressId]
            ]
        ];
        $response = $this->postRelationship(
            ['entity' => self::OWNER_ENTITY_TYPE, 'id' => (string)$newOwnerId, 'association' => 'addresses'],
            $data,
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToChangeOwnerViaOwnerAddressesDeleteRelationshipResource()
    {
        /** @var AbstractAddress $address */
        $address = $this->getReference(self::ANOTHER_OWNER_ADDRESS_2_REF);
        $addressId = $address->getId();
        $ownerId = $this->getOwner($address)->getId();

        $data = [
            'data' => [
                ['type' => self::ENTITY_TYPE, 'id' => (string)$addressId]
            ]
        ];
        $response = $this->deleteRelationship(
            ['entity' => self::OWNER_ENTITY_TYPE, 'id' => (string)$ownerId, 'association' => 'addresses'],
            $data,
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }
}
