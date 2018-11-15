<?php

namespace Oro\Bundle\AddressBundle\Tests\Functional\Api\RestJsonApi;

/**
 * Tests for "primaryEmail" and "emails" attributes.
 * This trait requires the following constants:
 * * ENTITY_CLASS
 * * ENTITY_TYPE
 * * CREATE_MIN_REQUEST_DATA
 * * ENTITY_WITHOUT_EMAILS_REF
 * * ENTITY_WITH_EMAILS_REF
 * * PRIMARY_EMAIL
 * * NOT_PRIMARY_EMAIL
 */
trait PrimaryEmailTestTrait
{
    public function testCreateWithPrimaryEmail()
    {
        $primaryEmail = 'primary@example.com';

        $data = $this->getRequestData(self::CREATE_MIN_REQUEST_DATA);
        $data['data']['attributes']['primaryEmail'] = $primaryEmail;
        $response = $this->post(
            ['entity' => self::ENTITY_TYPE],
            $data
        );

        $entityId = (int)$this->getResourceId($response);
        $expectedData = $data;
        $expectedData['data']['attributes']['emails'] = [
            ['email' => $primaryEmail]
        ];
        $this->assertResponseContains($expectedData, $response);

        $entity = $this->getEntityManager()->find(self::ENTITY_CLASS, $entityId);
        self::assertEquals($primaryEmail, $entity->getPrimaryEmail()->getEmail());
        self::assertCount(1, $entity->getEmails());
    }

    public function testCreateWithPrimaryEmailAndEmailsWhenPrimaryEmailExistsInEmails()
    {
        $primaryEmail = 'primary@example.com';
        $anotherEmail = 'another@example.com';

        $data = $this->getRequestData(self::CREATE_MIN_REQUEST_DATA);
        $data['data']['attributes']['primaryEmail'] = $primaryEmail;
        $data['data']['attributes']['emails'] = [
            ['email' => $primaryEmail],
            ['email' => $anotherEmail]
        ];
        $response = $this->post(
            ['entity' => self::ENTITY_TYPE],
            $data
        );

        $entityId = (int)$this->getResourceId($response);
        $this->assertResponseContains($data, $response);

        $entity = $this->getEntityManager()->find(self::ENTITY_CLASS, $entityId);
        self::assertEquals($primaryEmail, $entity->getPrimaryEmail()->getEmail());
        self::assertCount(2, $entity->getEmails());
    }

    public function testTryToCreateWithPrimaryEmailAndEmailsWhenPrimaryEmailDoesNotExistInEmails()
    {
        $primaryEmail = 'primary@example.com';
        $anotherEmail = 'another@example.com';

        $data = $this->getRequestData(self::CREATE_MIN_REQUEST_DATA);
        $data['data']['attributes']['primaryEmail'] = $primaryEmail;
        $data['data']['attributes']['emails'] = [
            ['email' => $anotherEmail]
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
                    'detail' => 'One of the emails must be set as primary.',
                    'source' => ['pointer' => '/data/attributes/emails']
                ],
                [
                    'title'  => 'primary item constraint',
                    'detail' => 'Unknown primary email address.',
                    'source' => ['pointer' => '/data/attributes/primaryEmail']
                ]
            ],
            $response
        );
    }

    public function testTryToCreateWithEmailsButWithoutPrimaryEmail()
    {
        $data = $this->getRequestData(self::CREATE_MIN_REQUEST_DATA);
        $data['data']['attributes']['emails'] = [
            ['email' => 'email1@example.com'],
            ['email' => 'email2@example.com']
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
                'detail' => 'One of the emails must be set as primary.',
                'source' => ['pointer' => '/data/attributes/emails']
            ],
            $response
        );
    }

    public function testUpdatePrimaryEmailWhenItExistsInEmails()
    {
        $entityId = $this->getReference(self::ENTITY_WITH_EMAILS_REF)->getId();

        $data = [
            'data' => [
                'type'       => self::ENTITY_TYPE,
                'id'         => (string)$entityId,
                'attributes' => [
                    'primaryEmail' => self::NOT_PRIMARY_EMAIL
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => self::ENTITY_TYPE, 'id' => $entityId],
            $data
        );

        $expectedData = $data;
        $expectedData['data']['attributes']['emails'] = [
            ['email' => self::NOT_PRIMARY_EMAIL],
            ['email' => self::PRIMARY_EMAIL]
        ];
        $this->assertResponseContains($expectedData, $response);

        $entity = $this->getEntityManager()->find(self::ENTITY_CLASS, $entityId);
        self::assertEquals(self::NOT_PRIMARY_EMAIL, $entity->getPrimaryEmail()->getEmail());
        self::assertCount(2, $entity->getEmails());
    }

    public function testUpdatePrimaryEmailWhenItDoesNotExistInEmails()
    {
        $primaryEmail = 'primary@example.com';

        $entityId = $this->getReference(self::ENTITY_WITH_EMAILS_REF)->getId();

        $data = [
            'data' => [
                'type'       => self::ENTITY_TYPE,
                'id'         => (string)$entityId,
                'attributes' => [
                    'primaryEmail' => $primaryEmail
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => self::ENTITY_TYPE, 'id' => $entityId],
            $data
        );

        $expectedData = $data;
        $expectedData['data']['attributes']['emails'] = [
            ['email' => self::NOT_PRIMARY_EMAIL],
            ['email' => $primaryEmail]
        ];
        $this->assertResponseContains($expectedData, $response);

        $entity = $this->getEntityManager()->find(self::ENTITY_CLASS, $entityId);
        self::assertEquals($primaryEmail, $entity->getPrimaryEmail()->getEmail());
        self::assertCount(2, $entity->getEmails());
    }

    public function testUpdatePrimaryEmailWhenNoEmails()
    {
        $primaryEmail = 'primary@example.com';

        $entityId = $this->getReference(self::ENTITY_WITHOUT_EMAILS_REF)->getId();

        $data = [
            'data' => [
                'type'       => self::ENTITY_TYPE,
                'id'         => (string)$entityId,
                'attributes' => [
                    'primaryEmail' => $primaryEmail
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => self::ENTITY_TYPE, 'id' => $entityId],
            $data
        );

        $expectedData = $data;
        $expectedData['data']['attributes']['emails'] = [
            ['email' => $primaryEmail]
        ];
        $this->assertResponseContains($expectedData, $response);

        $entity = $this->getEntityManager()->find(self::ENTITY_CLASS, $entityId);
        self::assertEquals($primaryEmail, $entity->getPrimaryEmail()->getEmail());
        self::assertCount(1, $entity->getEmails());
    }

    public function testTryToUpdatePrimaryEmailToNull()
    {
        $entityId = $this->getReference(self::ENTITY_WITH_EMAILS_REF)->getId();

        $data = [
            'data' => [
                'type'       => self::ENTITY_TYPE,
                'id'         => (string)$entityId,
                'attributes' => [
                    'primaryEmail' => null
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
                'detail' => 'One of the emails must be set as primary.',
                'source' => ['pointer' => '/data/attributes/emails']
            ],
            $response
        );
    }

    public function testTryToUpdateEmailsWhenExistingPrimaryEmailDoesNotExistInNewEmails()
    {
        $entityId = $this->getReference(self::ENTITY_WITH_EMAILS_REF)->getId();

        $data = [
            'data' => [
                'type'       => self::ENTITY_TYPE,
                'id'         => (string)$entityId,
                'attributes' => [
                    'emails' => [
                        ['email' => 'email1@example.com'],
                        ['email' => 'email2@example.com']
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
                'detail' => 'One of the emails must be set as primary.',
                'source' => ['pointer' => '/data/attributes/emails']
            ],
            $response
        );
    }

    public function testUpdateEmailsWhenExistingPrimaryEmailExistsInSubmittedEmails()
    {
        $anotherEmail = 'another@example.com';

        $entityId = $this->getReference(self::ENTITY_WITH_EMAILS_REF)->getId();

        $data = [
            'data' => [
                'type'       => self::ENTITY_TYPE,
                'id'         => (string)$entityId,
                'attributes' => [
                    'emails' => [
                        ['email' => self::PRIMARY_EMAIL],
                        ['email' => $anotherEmail]
                    ]
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => self::ENTITY_TYPE, 'id' => $entityId],
            $data
        );

        $expectedData = $data;
        $expectedData['data']['attributes']['primaryEmail'] = self::PRIMARY_EMAIL;
        $expectedData['data']['attributes']['emails'] = [
            ['email' => self::PRIMARY_EMAIL],
            ['email' => $anotherEmail]
        ];
        $this->assertResponseContains($expectedData, $response);

        $entity = $this->getEntityManager()->find(self::ENTITY_CLASS, $entityId);
        self::assertEquals(self::PRIMARY_EMAIL, $entity->getPrimaryEmail()->getEmail());
        self::assertCount(2, $entity->getEmails());
    }

    public function testUpdatePrimaryEmailAndEmailsWhenPrimaryEmailExistsInEmails()
    {
        $primaryEmail = 'primary@example.com';
        $anotherEmail = 'another@example.com';

        $entityId = $this->getReference(self::ENTITY_WITH_EMAILS_REF)->getId();

        $data = [
            'data' => [
                'type'       => self::ENTITY_TYPE,
                'id'         => (string)$entityId,
                'attributes' => [
                    'primaryEmail' => $primaryEmail,
                    'emails'       => [
                        ['email' => $primaryEmail],
                        ['email' => $anotherEmail]
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
        self::assertEquals($primaryEmail, $entity->getPrimaryEmail()->getEmail());
        self::assertCount(2, $entity->getEmails());
    }

    public function testTryToUpdatePrimaryEmailAndEmailsWhenPrimaryEmailDoesNotExistInEmails()
    {
        $primaryEmail = 'primary@example.com';
        $anotherEmail = 'another@example.com';

        $entityId = $this->getReference(self::ENTITY_WITH_EMAILS_REF)->getId();

        $data = [
            'data' => [
                'type'       => self::ENTITY_TYPE,
                'id'         => (string)$entityId,
                'attributes' => [
                    'primaryEmail' => $primaryEmail,
                    'emails'       => [
                        ['email' => $anotherEmail]
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
                    'detail' => 'One of the emails must be set as primary.',
                    'source' => ['pointer' => '/data/attributes/emails']
                ],
                [
                    'title'  => 'primary item constraint',
                    'detail' => 'Unknown primary email address.',
                    'source' => ['pointer' => '/data/attributes/primaryEmail']
                ]
            ],
            $response
        );
    }

    public function testUpdateEmailsToEmptyArray()
    {
        $entityId = $this->getReference(self::ENTITY_WITH_EMAILS_REF)->getId();

        $data = [
            'data' => [
                'type'       => self::ENTITY_TYPE,
                'id'         => (string)$entityId,
                'attributes' => [
                    'emails' => []
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => self::ENTITY_TYPE, 'id' => $entityId],
            $data
        );

        $expectedData = $data;
        $expectedData['data']['attributes']['primaryEmail'] = null;
        $this->assertResponseContains($expectedData, $response);

        $entity = $this->getEntityManager()->find(self::ENTITY_CLASS, $entityId);
        self::assertNull($entity->getPrimaryEmail());
        self::assertCount(0, $entity->getEmails());
    }
}
