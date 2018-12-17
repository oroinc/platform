<?php

namespace Oro\Bundle\AddressBundle\Tests\Functional\Api\RestJsonApi;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\ApiBundle\Request\ApiActions;
use Oro\Bundle\FormBundle\Entity\PrimaryItem;

/**
 * Tests for address "primary" attribute.
 * This trait requires the following constants:
 * * ENTITY_CLASS
 * * ENTITY_TYPE
 * * OWNER_ENTITY_TYPE
 * * OWNER_RELATIONSHIP
 * * CREATE_MIN_REQUEST_DATA
 * * PRIMARY_ADDRESS_REF
 * and the following methods:
 * * getOwner($address)
 */
trait PrimaryAddressTestTrait
{
    /**
     * @param Collection $addresses
     */
    private function removeAllAddressesExceptPrimary(Collection $addresses)
    {
        $toRemoveAddresses = [];
        /** @var AbstractAddress|PrimaryItem $address */
        foreach ($addresses as $address) {
            if (!$address->isPrimary()) {
                $toRemoveAddresses[] = $address;
            }
        }
        foreach ($toRemoveAddresses as $address) {
            $addresses->removeElement($address);
        }
    }

    /**
     * @param string[] $addressIds
     */
    private function assertOneAndOnlyOneAddressIsPrimary(array $addressIds)
    {
        $numberOfPrimaryAddresses = 0;
        /** @var AbstractAddress|PrimaryItem $address */
        foreach ($addressIds as $addressId) {
            /** @var AbstractAddress|PrimaryItem $newAddress */
            $address = $this->getEntityManager()
                ->find(self::ENTITY_CLASS, $addressId);
            if ($address->isPrimary()) {
                $numberOfPrimaryAddresses++;
            }
        }
        self::assertSame(1, $numberOfPrimaryAddresses, 'Only one address can be a primary');
    }

    public function testCreateOneMorePrimaryAddress()
    {
        /** @var AbstractAddress|PrimaryItem $existingAddress */
        $existingAddress = $this->getReference(self::PRIMARY_ADDRESS_REF);
        $existingAddressId = $existingAddress->getId();
        /** @var object $owner */
        $owner = $this->getOwner($existingAddress);
        $ownerId = $owner->getId();
        $this->removeAllAddressesExceptPrimary($owner->getAddresses());

        // guard
        self::assertCount(1, $owner->getAddresses());
        self::assertTrue($existingAddress->isPrimary());

        $data = $this->getRequestData(self::CREATE_MIN_REQUEST_DATA);
        $data['data']['relationships'][self::OWNER_RELATIONSHIP]['data'] = [
            'type' => self::OWNER_ENTITY_TYPE,
            'id'   => (string)$ownerId
        ];
        $data['data']['attributes']['primary'] = true;
        $response = $this->post(
            ['entity' => self::ENTITY_TYPE],
            $data
        );

        $this->assertOneAndOnlyOneAddressIsPrimary([
            $this->getResourceId($response),
            $existingAddressId
        ]);
    }

    public function testCreateOneMorePrimaryAddressViaOwnerEntityUpdateResource()
    {
        if (!$this->isActionEnabled($this->getEntityClass(self::OWNER_ENTITY_TYPE), ApiActions::UPDATE)) {
            self::markTestSkipped('The "update" action is disabled for owner entity');
        }

        /** @var AbstractAddress|PrimaryItem $existingAddress */
        $existingAddress = $this->getReference(self::PRIMARY_ADDRESS_REF);
        $existingAddressId = $existingAddress->getId();
        /** @var object $owner */
        $owner = $this->getOwner($existingAddress);
        $ownerId = $owner->getId();
        $this->removeAllAddressesExceptPrimary($owner->getAddresses());

        // guard
        self::assertCount(1, $owner->getAddresses());
        self::assertTrue($existingAddress->isPrimary());

        $addressData = $this->getRequestData(self::CREATE_MIN_REQUEST_DATA);
        $addressData['data']['id'] = 'new_address';
        $data['data']['relationships'][self::OWNER_RELATIONSHIP]['data'] = [
            'type' => self::OWNER_ENTITY_TYPE,
            'id'   => (string)$ownerId
        ];
        $addressData['data']['attributes']['primary'] = true;
        $data = [
            'data'     => [
                'type'          => self::OWNER_ENTITY_TYPE,
                'id'            => (string)$ownerId,
                'relationships' => [
                    'addresses' => [
                        'data' => [
                            ['type' => self::ENTITY_TYPE, 'id' => (string)$existingAddressId],
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

        $this->assertOneAndOnlyOneAddressIsPrimary([
            self::getNewResourceIdFromIncludedSection($response, 'new_address'),
            $existingAddressId
        ]);
    }

    public function testCreateNotPrimaryAddress()
    {
        /** @var object $owner */
        $owner = $this->getOwner($this->getReference(self::PRIMARY_ADDRESS_REF));
        $ownerId = $owner->getId();
        $owner->getAddresses()->clear();
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        // guard
        self::assertCount(0, $owner->getAddresses());

        $data = $this->getRequestData(self::CREATE_MIN_REQUEST_DATA);
        $data['data']['relationships'][self::OWNER_RELATIONSHIP]['data'] = [
            'type' => self::OWNER_ENTITY_TYPE,
            'id'   => (string)$ownerId
        ];
        $data['data']['attributes']['primary'] = false;
        $response = $this->post(
            ['entity' => self::ENTITY_TYPE],
            $data
        );

        /** @var AbstractAddress|PrimaryItem $newAddress */
        $newAddress = $this->getEntityManager()
            ->find(self::ENTITY_CLASS, (int)$this->getResourceId($response));
        self::assertTrue($newAddress->isPrimary());
    }

    public function testCreateNotPrimaryAddressWithOwnerEntityRelationshipViaOwnerEntityUpdateResource()
    {
        if (!$this->isActionEnabled($this->getEntityClass(self::OWNER_ENTITY_TYPE), ApiActions::UPDATE)) {
            self::markTestSkipped('The "update" action is disabled for owner entity');
        }

        /** @var object $owner */
        $owner = $this->getOwner($this->getReference(self::PRIMARY_ADDRESS_REF));
        $ownerId = $owner->getId();
        $owner->getAddresses()->clear();
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        // guard
        self::assertCount(0, $owner->getAddresses());

        $addressData = $this->getRequestData(self::CREATE_MIN_REQUEST_DATA);
        $addressData['data']['id'] = 'new_address';
        $data['data']['relationships'][self::OWNER_RELATIONSHIP]['data'] = [
            'type' => self::OWNER_ENTITY_TYPE,
            'id'   => (string)$ownerId
        ];
        $addressData['data']['attributes']['primary'] = false;
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

        /** @var AbstractAddress|PrimaryItem $newAddress */
        $newAddress = $this->getEntityManager()
            ->find(self::ENTITY_CLASS, self::getNewResourceIdFromIncludedSection($response, 'new_address'));
        self::assertTrue($newAddress->isPrimary());
    }

    public function testCreateNotPrimaryAddressWithoutOwnerEntityRelationshipViaOwnerEntityUpdateResource()
    {
        if (!$this->isActionEnabled($this->getEntityClass(self::OWNER_ENTITY_TYPE), ApiActions::UPDATE)) {
            self::markTestSkipped('The "update" action is disabled for owner entity');
        }

        /** @var object $owner */
        $owner = $this->getOwner($this->getReference(self::PRIMARY_ADDRESS_REF));
        $ownerId = $owner->getId();
        $owner->getAddresses()->clear();
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        // guard
        self::assertCount(0, $owner->getAddresses());

        $addressData = $this->getRequestData(self::CREATE_MIN_REQUEST_DATA);
        $addressData['data']['id'] = 'new_address';
        unset($addressData['data']['relationships'][self::OWNER_RELATIONSHIP]);
        $addressData['data']['attributes']['primary'] = false;
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

        /** @var AbstractAddress|PrimaryItem $newAddress */
        $newAddress = $this->getEntityManager()
            ->find(self::ENTITY_CLASS, self::getNewResourceIdFromIncludedSection($response, 'new_address'));
        self::assertTrue($newAddress->isPrimary());
    }

    public function testCreateSeveralPrimaryAddressesWithOwnerEntityRelationshipViaOwnerEntityUpdateResource()
    {
        if (!$this->isActionEnabled($this->getEntityClass(self::OWNER_ENTITY_TYPE), ApiActions::UPDATE)) {
            self::markTestSkipped('The "update" action is disabled for owner entity');
        }

        /** @var object $owner */
        $owner = $this->getOwner($this->getReference(self::PRIMARY_ADDRESS_REF));
        $ownerId = $owner->getId();
        $owner->getAddresses()->clear();
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        // guard
        self::assertCount(0, $owner->getAddresses());

        $addressData1 = $this->getRequestData(self::CREATE_MIN_REQUEST_DATA);
        $addressData1['data']['id'] = 'new_address1';
        $addressData1['data']['relationships'][self::OWNER_RELATIONSHIP]['data'] = [
            'type' => self::OWNER_ENTITY_TYPE,
            'id'   => (string)$ownerId
        ];
        $addressData1['data']['attributes']['primary'] = false;
        $addressData2 = $this->getRequestData(self::CREATE_MIN_REQUEST_DATA);
        $addressData2['data']['id'] = 'new_address2';
        $addressData2['data']['relationships'][self::OWNER_RELATIONSHIP]['data'] = [
            'type' => self::OWNER_ENTITY_TYPE,
            'id'   => (string)$ownerId
        ];
        $addressData2['data']['attributes']['primary'] = false;
        $data = [
            'data'     => [
                'type'          => self::OWNER_ENTITY_TYPE,
                'id'            => (string)$ownerId,
                'relationships' => [
                    'addresses' => [
                        'data' => [
                            ['type' => self::ENTITY_TYPE, 'id' => 'new_address1'],
                            ['type' => self::ENTITY_TYPE, 'id' => 'new_address2']
                        ]
                    ]
                ]
            ],
            'included' => [
                $addressData1['data'],
                $addressData2['data']
            ]
        ];
        $response = $this->patch(
            ['entity' => self::OWNER_ENTITY_TYPE, 'id' => (string)$ownerId],
            $data
        );

        $this->assertOneAndOnlyOneAddressIsPrimary([
            self::getNewResourceIdFromIncludedSection($response, 'new_address1'),
            self::getNewResourceIdFromIncludedSection($response, 'new_address2')
        ]);
    }

    public function testCreateSeveralPrimaryAddressesWithoutOwnerEntityRelationshipViaOwnerEntityUpdateResource()
    {
        if (!$this->isActionEnabled($this->getEntityClass(self::OWNER_ENTITY_TYPE), ApiActions::UPDATE)) {
            self::markTestSkipped('The "update" action is disabled for owner entity');
        }

        /** @var object $owner */
        $owner = $this->getOwner($this->getReference(self::PRIMARY_ADDRESS_REF));
        $ownerId = $owner->getId();
        $owner->getAddresses()->clear();
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        // guard
        self::assertCount(0, $owner->getAddresses());

        $addressData1 = $this->getRequestData(self::CREATE_MIN_REQUEST_DATA);
        $addressData1['data']['id'] = 'new_address1';
        unset($addressData1['data']['relationships'][self::OWNER_RELATIONSHIP]);
        $addressData1['data']['attributes']['primary'] = false;
        $addressData2 = $this->getRequestData(self::CREATE_MIN_REQUEST_DATA);
        $addressData2['data']['id'] = 'new_address2';
        unset($addressData2['data']['relationships'][self::OWNER_RELATIONSHIP]);
        $addressData2['data']['attributes']['primary'] = false;
        $data = [
            'data'     => [
                'type'          => self::OWNER_ENTITY_TYPE,
                'id'            => (string)$ownerId,
                'relationships' => [
                    'addresses' => [
                        'data' => [
                            ['type' => self::ENTITY_TYPE, 'id' => 'new_address1'],
                            ['type' => self::ENTITY_TYPE, 'id' => 'new_address2']
                        ]
                    ]
                ]
            ],
            'included' => [
                $addressData1['data'],
                $addressData2['data']
            ]
        ];
        $response = $this->patch(
            ['entity' => self::OWNER_ENTITY_TYPE, 'id' => (string)$ownerId],
            $data
        );

        $this->assertOneAndOnlyOneAddressIsPrimary([
            self::getNewResourceIdFromIncludedSection($response, 'new_address1'),
            self::getNewResourceIdFromIncludedSection($response, 'new_address2')
        ]);
    }
}
