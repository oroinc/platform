<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Tests\Functional\Api\DataFixtures\LoadEmailData;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Test\Functional\RolePermissionExtension;
use Symfony\Component\HttpFoundation\Response;

class EmailAttachmentTest extends RestJsonApiTestCase
{
    use RolePermissionExtension;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([LoadEmailData::class]);
        $this->updateRolePermissions(
            'ROLE_ADMINISTRATOR',
            EmailUser::class,
            [
                'VIEW'         => AccessLevel::GLOBAL_LEVEL,
                'VIEW_PRIVATE' => AccessLevel::LOCAL_LEVEL,
                'CREATE'       => AccessLevel::GLOBAL_LEVEL,
                'DELETE'       => AccessLevel::GLOBAL_LEVEL,
                'ASSIGN'       => AccessLevel::GLOBAL_LEVEL,
                'EDIT'         => AccessLevel::GLOBAL_LEVEL
            ]
        );
    }

    public function testGetList(): void
    {
        $response = $this->cget(['entity' => 'emailattachments']);
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'          => 'emailattachments',
                        'id'            => '<toString(@emailAttachment_1_1->id)>',
                        'attributes'    => [
                            'fileName'          => 'test.png',
                            'contentType'       => 'image/png',
                            'embeddedContentId' => null,
                            'contentEncoding'   => 'base64',
                            'content'           => LoadEmailData::ENCODED_ATTACHMENT_CONTENT
                        ],
                        'relationships' => [
                            'email' => [
                                'data' => ['type' => 'emails', 'id' => '<toString(@email_1->id)>']
                            ]
                        ]
                    ],
                    [
                        'type'          => 'emailattachments',
                        'id'            => '<toString(@emailAttachment_3_1->id)>',
                        'attributes'    => [
                            'fileName'          => 'test.png',
                            'contentType'       => 'image/png',
                            'embeddedContentId' => '1234567890',
                            'contentEncoding'   => 'base64',
                            'content'           => LoadEmailData::ENCODED_ATTACHMENT_CONTENT
                        ],
                        'relationships' => [
                            'email' => [
                                'data' => ['type' => 'emails', 'id' => '<toString(@email_3->id)>']
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetListFilterByEmail(): void
    {
        $response = $this->cget(
            ['entity' => 'emailattachments'],
            ['filter[email]' => '<toString(@email_3->id)>']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'          => 'emailattachments',
                        'id'            => '<toString(@emailAttachment_3_1->id)>',
                        'attributes'    => [
                            'fileName'          => 'test.png',
                            'contentType'       => 'image/png',
                            'embeddedContentId' => '1234567890',
                            'contentEncoding'   => 'base64',
                            'content'           => LoadEmailData::ENCODED_ATTACHMENT_CONTENT
                        ],
                        'relationships' => [
                            'email' => [
                                'data' => ['type' => 'emails', 'id' => '<toString(@email_3->id)>']
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGet(): void
    {
        $response = $this->get(['entity' => 'emailattachments', 'id' => '<toString(@emailAttachment_1_1->id)>']);
        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => 'emailattachments',
                    'id'            => '<toString(@emailAttachment_1_1->id)>',
                    'attributes'    => [
                        'fileName'          => 'test.png',
                        'contentType'       => 'image/png',
                        'embeddedContentId' => null,
                        'contentEncoding'   => 'base64',
                        'content'           => LoadEmailData::ENCODED_ATTACHMENT_CONTENT
                    ],
                    'relationships' => [
                        'email' => [
                            'data' => ['type' => 'emails', 'id' => '<toString(@email_1->id)>']
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testTryToGetNotAccessible(): void
    {
        $response = $this->get(
            ['entity' => 'emailattachments', 'id' => '<toString(@emailAttachment_4_1->id)>'],
            [],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'access denied exception',
                'detail' => 'No access to the entity.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testTryToCreate(): void
    {
        $response = $this->post(
            ['entity' => 'emailattachments'],
            [
                'data' => [
                    'type'          => 'emailattachments',
                    'attributes'    => [
                        'fileName'          => 'test.png',
                        'contentType'       => 'image/png',
                        'embeddedContentId' => null,
                        'contentEncoding'   => 'base64',
                        'content'           => LoadEmailData::ENCODED_ATTACHMENT_CONTENT
                    ],
                    'relationships' => [
                        'email' => [
                            'data' => ['type' => 'emails', 'id' => '<toString(@email_1->id)>']
                        ]
                    ]
                ]
            ],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'access denied exception',
                'detail' => 'Use API resource to create an email.'
                    . ' An email attachment can be created only together with an email.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testTryToUpdate(): void
    {
        $response = $this->patch(
            ['entity' => 'emailattachments', 'id' => '<toString(@emailAttachment_1_1->id)>'],
            [
                'data' => [
                    'type'       => 'emailattachments',
                    'id'         => '<toString(@emailAttachment_1_1->id)>',
                    'attributes' => [
                        'fileName' => 'test.png'
                    ]
                ]
            ],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDelete(): void
    {
        $response = $this->delete(
            ['entity' => 'emailattachments', 'id' => '<toString(@emailAttachment_1_1->id)>'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDeleteList(): void
    {
        $response = $this->cdelete(
            ['entity' => 'emailattachments'],
            ['filter[id]' => '<toString(@emailAttachment_1_1->id)>'],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET, POST');
    }

    public function testTryToGetSubresourceForEmail(): void
    {
        $response = $this->getSubresource(
            ['entity' => 'emailattachments', 'id' => '<toString(@emailAttachment_1_1->id)>', 'association' => 'email'],
            [],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'relationship constraint',
                'detail' => 'Unsupported subresource.'
            ],
            $response,
            Response::HTTP_NOT_FOUND
        );
    }

    public function testTryToGetRelationshipForEmail(): void
    {
        $response = $this->getRelationship(
            ['entity' => 'emailattachments', 'id' => '<toString(@emailAttachment_1_1->id)>', 'association' => 'email'],
            [],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'relationship constraint',
                'detail' => 'Unsupported subresource.'
            ],
            $response,
            Response::HTTP_NOT_FOUND
        );
    }
}
