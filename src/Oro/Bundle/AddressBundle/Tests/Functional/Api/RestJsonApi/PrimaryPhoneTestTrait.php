<?php

namespace Oro\Bundle\AddressBundle\Tests\Functional\Api\RestJsonApi;

/**
 * Tests for "primaryPhone" and "phones" attributes.
 * This trait requires the following constants:
 * * ENTITY_CLASS
 * * ENTITY_TYPE
 * * CREATE_MIN_REQUEST_DATA
 * * ENTITY_WITHOUT_PHONES_REF
 * * ENTITY_WITH_PHONES_REF
 * * PRIMARY_PHONE
 * * NOT_PRIMARY_PHONE
 */
trait PrimaryPhoneTestTrait
{
    public function testCreateWithPrimaryPhone()
    {
        $primaryPhone = '9998887777';

        $data = $this->getRequestData(self::CREATE_MIN_REQUEST_DATA);
        $data['data']['attributes']['primaryPhone'] = $primaryPhone;
        $response = $this->post(
            ['entity' => self::ENTITY_TYPE],
            $data
        );

        $entityId = (int)$this->getResourceId($response);
        $expectedData = $data;
        $expectedData['data']['attributes']['phones'] = [
            ['phone' => $primaryPhone]
        ];
        $this->assertResponseContains($expectedData, $response);

        $entity = $this->getEntityManager()->find(self::ENTITY_CLASS, $entityId);
        self::assertEquals($primaryPhone, $entity->getPrimaryPhone()->getPhone());
        self::assertCount(1, $entity->getPhones());
    }

    public function testCreateWithPrimaryPhoneAndPhonesWhenPrimaryPhoneExistsInPhones()
    {
        $primaryPhone = '9998887777';
        $anotherPhone = '7778889999';

        $data = $this->getRequestData(self::CREATE_MIN_REQUEST_DATA);
        $data['data']['attributes']['primaryPhone'] = $primaryPhone;
        $data['data']['attributes']['phones'] = [
            ['phone' => $primaryPhone],
            ['phone' => $anotherPhone]
        ];
        $response = $this->post(
            ['entity' => self::ENTITY_TYPE],
            $data
        );

        $entityId = (int)$this->getResourceId($response);
        $this->assertResponseContains($data, $response);

        $entity = $this->getEntityManager()->find(self::ENTITY_CLASS, $entityId);
        self::assertEquals($primaryPhone, $entity->getPrimaryPhone()->getPhone());
        self::assertCount(2, $entity->getPhones());
    }

    public function testTryToCreateWithPrimaryPhoneAndPhonesWhenPrimaryPhoneDoesNotExistInPhones()
    {
        $primaryPhone = '9998887777';
        $anotherPhone = '7778889999';

        $data = $this->getRequestData(self::CREATE_MIN_REQUEST_DATA);
        $data['data']['attributes']['primaryPhone'] = $primaryPhone;
        $data['data']['attributes']['phones'] = [
            ['phone' => $anotherPhone]
        ];
        $response = $this->post(
            ['entity' => self::ENTITY_TYPE],
            $data,
            [],
            false
        );

        $this->assertResponseValidationErrors(
            [
                [
                    'title'  => 'contains primary constraint',
                    'detail' => 'One of the phones must be set as primary.',
                    'source' => ['pointer' => '/data/attributes/phones']
                ],
                [
                    'title'  => 'primary item constraint',
                    'detail' => 'Unknown primary phone number.',
                    'source' => ['pointer' => '/data/attributes/primaryPhone']
                ]
            ],
            $response
        );
    }

    public function testTryToCreateWithPhonesButWithoutPrimaryPhone()
    {
        $data = $this->getRequestData(self::CREATE_MIN_REQUEST_DATA);
        $data['data']['attributes']['phones'] = [
            ['phone' => '3334441111'],
            ['phone' => '3334441112']
        ];
        $response = $this->post(
            ['entity' => self::ENTITY_TYPE],
            $data,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'contains primary constraint',
                'detail' => 'One of the phones must be set as primary.',
                'source' => ['pointer' => '/data/attributes/phones']
            ],
            $response
        );
    }

    public function testUpdatePrimaryPhoneWhenItExistsInPhones()
    {
        $entityId = $this->getReference(self::ENTITY_WITH_PHONES_REF)->getId();

        $data = [
            'data' => [
                'type'       => self::ENTITY_TYPE,
                'id'         => (string)$entityId,
                'attributes' => [
                    'primaryPhone' => self::NOT_PRIMARY_PHONE
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => self::ENTITY_TYPE, 'id' => $entityId],
            $data
        );

        $expectedData = $data;
        $expectedData['data']['attributes']['phones'] = [
            ['phone' => self::NOT_PRIMARY_PHONE],
            ['phone' => self::PRIMARY_PHONE]
        ];
        $this->assertResponseContains($expectedData, $response);

        $entity = $this->getEntityManager()->find(self::ENTITY_CLASS, $entityId);
        self::assertEquals(self::NOT_PRIMARY_PHONE, $entity->getPrimaryPhone()->getPhone());
        self::assertCount(2, $entity->getPhones());
    }

    public function testUpdatePrimaryPhoneWhenItDoesNotExistInPhones()
    {
        $primaryPhone = '9998887777';

        $entityId = $this->getReference(self::ENTITY_WITH_PHONES_REF)->getId();

        $data = [
            'data' => [
                'type'       => self::ENTITY_TYPE,
                'id'         => (string)$entityId,
                'attributes' => [
                    'primaryPhone' => $primaryPhone
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => self::ENTITY_TYPE, 'id' => $entityId],
            $data
        );

        $expectedData = $data;
        $expectedData['data']['attributes']['phones'] = [
            ['phone' => self::NOT_PRIMARY_PHONE],
            ['phone' => $primaryPhone]
        ];
        $this->assertResponseContains($expectedData, $response);

        $entity = $this->getEntityManager()->find(self::ENTITY_CLASS, $entityId);
        self::assertEquals($primaryPhone, $entity->getPrimaryPhone()->getPhone());
        self::assertCount(2, $entity->getPhones());
    }

    public function testUpdatePrimaryPhoneWhenNoPhones()
    {
        $primaryPhone = '9998887777';

        $entityId = $this->getReference(self::ENTITY_WITHOUT_PHONES_REF)->getId();

        $data = [
            'data' => [
                'type'       => self::ENTITY_TYPE,
                'id'         => (string)$entityId,
                'attributes' => [
                    'primaryPhone' => $primaryPhone
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => self::ENTITY_TYPE, 'id' => $entityId],
            $data
        );

        $expectedData = $data;
        $expectedData['data']['attributes']['phones'] = [
            ['phone' => $primaryPhone]
        ];
        $this->assertResponseContains($expectedData, $response);

        $entity = $this->getEntityManager()->find(self::ENTITY_CLASS, $entityId);
        self::assertEquals($primaryPhone, $entity->getPrimaryPhone()->getPhone());
        self::assertCount(1, $entity->getPhones());
    }

    public function testTryToUpdatePrimaryPhoneToNull()
    {
        $entityId = $this->getReference(self::ENTITY_WITH_PHONES_REF)->getId();

        $data = [
            'data' => [
                'type'       => self::ENTITY_TYPE,
                'id'         => (string)$entityId,
                'attributes' => [
                    'primaryPhone' => null
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => self::ENTITY_TYPE, 'id' => $entityId],
            $data,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'contains primary constraint',
                'detail' => 'One of the phones must be set as primary.',
                'source' => ['pointer' => '/data/attributes/phones']
            ],
            $response
        );
    }

    public function testTryToUpdatePhonesWhenExistingPrimaryPhoneDoesNotExistInNewPhones()
    {
        $entityId = $this->getReference(self::ENTITY_WITH_PHONES_REF)->getId();

        $data = [
            'data' => [
                'type'       => self::ENTITY_TYPE,
                'id'         => (string)$entityId,
                'attributes' => [
                    'phones' => [
                        ['phone' => '3334441111'],
                        ['phone' => '3334441112']
                    ]
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => self::ENTITY_TYPE, 'id' => $entityId],
            $data,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'contains primary constraint',
                'detail' => 'One of the phones must be set as primary.',
                'source' => ['pointer' => '/data/attributes/phones']
            ],
            $response
        );
    }

    public function testUpdatePhonesWhenExistingPrimaryPhoneExistsInSubmittedPhones()
    {
        $anotherPhone = '7778889999';

        $entityId = $this->getReference(self::ENTITY_WITH_PHONES_REF)->getId();

        $data = [
            'data' => [
                'type'       => self::ENTITY_TYPE,
                'id'         => (string)$entityId,
                'attributes' => [
                    'phones' => [
                        ['phone' => self::PRIMARY_PHONE],
                        ['phone' => $anotherPhone]
                    ]
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => self::ENTITY_TYPE, 'id' => $entityId],
            $data
        );

        $expectedData = $data;
        $expectedData['data']['attributes']['primaryPhone'] = self::PRIMARY_PHONE;
        $expectedData['data']['attributes']['phones'] = [
            ['phone' => self::PRIMARY_PHONE],
            ['phone' => $anotherPhone]
        ];
        $this->assertResponseContains($expectedData, $response);

        $entity = $this->getEntityManager()->find(self::ENTITY_CLASS, $entityId);
        self::assertEquals(self::PRIMARY_PHONE, $entity->getPrimaryPhone()->getPhone());
        self::assertCount(2, $entity->getPhones());
    }

    public function testUpdatePrimaryPhoneAndPhonesWhenPrimaryPhoneExistsInPhones()
    {
        $primaryPhone = '9998887777';
        $anotherPhone = '7778889999';

        $entityId = $this->getReference(self::ENTITY_WITH_PHONES_REF)->getId();

        $data = [
            'data' => [
                'type'       => self::ENTITY_TYPE,
                'id'         => (string)$entityId,
                'attributes' => [
                    'primaryPhone' => $primaryPhone,
                    'phones'       => [
                        ['phone' => $primaryPhone],
                        ['phone' => $anotherPhone]
                    ]
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => self::ENTITY_TYPE, 'id' => $entityId],
            $data
        );

        $this->assertResponseContains($data, $response);

        $entity = $this->getEntityManager()->find(self::ENTITY_CLASS, $entityId);
        self::assertEquals($primaryPhone, $entity->getPrimaryPhone()->getPhone());
        self::assertCount(2, $entity->getPhones());
    }

    public function testTryToUpdatePrimaryPhoneAndPhonesWhenPrimaryPhoneDoesNotExistInPhones()
    {
        $primaryPhone = '9998887777';
        $anotherPhone = '7778889999';

        $entityId = $this->getReference(self::ENTITY_WITH_PHONES_REF)->getId();

        $data = [
            'data' => [
                'type'       => self::ENTITY_TYPE,
                'id'         => (string)$entityId,
                'attributes' => [
                    'primaryPhone' => $primaryPhone,
                    'phones'       => [
                        ['phone' => $anotherPhone]
                    ]
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => self::ENTITY_TYPE, 'id' => $entityId],
            $data,
            [],
            false
        );

        $this->assertResponseValidationErrors(
            [
                [
                    'title'  => 'contains primary constraint',
                    'detail' => 'One of the phones must be set as primary.',
                    'source' => ['pointer' => '/data/attributes/phones']
                ],
                [
                    'title'  => 'primary item constraint',
                    'detail' => 'Unknown primary phone number.',
                    'source' => ['pointer' => '/data/attributes/primaryPhone']
                ]
            ],
            $response
        );
    }

    public function testUpdatePhonesToEmptyArray()
    {
        $entityId = $this->getReference(self::ENTITY_WITH_PHONES_REF)->getId();

        $data = [
            'data' => [
                'type'       => self::ENTITY_TYPE,
                'id'         => (string)$entityId,
                'attributes' => [
                    'phones' => []
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => self::ENTITY_TYPE, 'id' => $entityId],
            $data
        );

        $expectedData = $data;
        $expectedData['data']['attributes']['primaryPhone'] = null;
        $this->assertResponseContains($expectedData, $response);

        $entity = $this->getEntityManager()->find(self::ENTITY_CLASS, $entityId);
        self::assertNull($entity->getPrimaryPhone());
        self::assertCount(0, $entity->getPhones());
    }
}
