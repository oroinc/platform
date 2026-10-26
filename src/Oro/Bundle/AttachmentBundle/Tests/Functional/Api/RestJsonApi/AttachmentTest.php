<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\AttachmentBundle\Entity\Attachment;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Tests\Functional\Stub\ExternalFileFactoryStub;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class AttachmentTest extends RestJsonApiTestCase
{
    private const string BLANK_FILE_CONTENT = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABAQMAAAAl21bKAAAAA'
        . '1BMVEUAAACnej3aAAAAAXRSTlMAQObYZgAAAApJREFUCNdjYAAAAAIAAeIhvDMAAAAASUVORK5CYII=';
    private const string SVG_FILE_PATH = __DIR__ . '/../../DataFixtures/files/image.svg';
    private const string INVALID_MIME_TYPE_MESSAGE = 'The MIME type of the file is invalid ("%s").'
        . ' Allowed MIME types are "image/*".';
    private const string ATTACHMENT_EXTERNAL_FILE_URLS_REGEXP = 'oro_attachment.external_file_allowed_urls_regexp';

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            '@OroAttachmentBundle/Tests/Functional/Api/DataFixtures/attachment_data.yml'
        ]);
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->restoreConfigValues();
        parent::tearDown();
    }

    private function getTargetId(Attachment $attachment): ?int
    {
        return $attachment->getTarget()?->getId();
    }

    public function testGetList(): void
    {
        $response = $this->cget(['entity' => 'attachments']);
        $this->assertResponseContains('cget_attachment.yml', $response);
    }

    public function testGetListWithTargetInIncludeFilter(): void
    {
        $response = $this->cget(
            ['entity' => 'attachments'],
            ['include' => 'target,target.organization']
        );
        $this->assertResponseContains(
            [
                'data'     => [
                    [
                        'type'          => 'attachments',
                        'id'            => '<toString(@attachment1->id)>',
                        'relationships' => [
                            'target' => [
                                'data' => ['type' => 'testapidepartments', 'id' => '<toString(@department1->id)>']
                            ]
                        ]
                    ],
                    [
                        'type'          => 'attachments',
                        'id'            => '<toString(@attachment2->id)>',
                        'relationships' => [
                            'target' => [
                                'data' => ['type' => 'testapidepartments', 'id' => '<toString(@department1->id)>']
                            ]
                        ]
                    ],
                    [
                        'type'          => 'attachments',
                        'id'            => '<toString(@attachment3->id)>',
                        'relationships' => [
                            'target' => [
                                'data' => ['type' => 'testapidepartments', 'id' => '<toString(@department2->id)>']
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type'       => 'organizations',
                        'id'         => '<toString(@organization->id)>',
                        'attributes' => [
                            'name' => '<toString(@organization->name)>'
                        ]
                    ],
                    [
                        'type'       => 'testapidepartments',
                        'id'         => '<toString(@department1->id)>',
                        'attributes' => [
                            'title' => '<toString(@department1->name)>'
                        ]
                    ],
                    [
                        'type'       => 'testapidepartments',
                        'id'         => '<toString(@department2->id)>',
                        'attributes' => [
                            'title' => '<toString(@department2->name)>'
                        ]
                    ]
                ]
            ],
            $response
        );
        $responseContent = self::jsonToArray($response->getContent());
        self::assertCount(3, $responseContent['included'], 'included');
    }

    public function testGet(): void
    {
        $response = $this->get(
            ['entity' => 'attachments', 'id' => '<toString(@attachment1->id)>']
        );
        $this->assertResponseContains('get_attachment.yml', $response);
    }

    public function testCreate(): void
    {
        $department1Id = $this->getReference('department1')->getId();
        $response = $this->post(
            ['entity' => 'attachments'],
            [
                'data' => [
                    'type'          => 'attachments',
                    'attributes'    => [
                        'comment' => 'Comment for test attachment'
                    ],
                    'relationships' => [
                        'file'   => [
                            'data' => ['type' => 'files', 'id' => 'file']
                        ],
                        'target' => [
                            'data' => ['type' => 'testapidepartments', 'id' => '<toString(@department1->id)>']
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type'          => 'files',
                        'id'            => 'file',
                        'attributes' => [
                            'mimeType'         => 'image/png',
                            'originalFilename' => 'blank.png',
                            'content'          => self::BLANK_FILE_CONTENT
                        ]
                    ]
                ]
            ]
        );

        $attachmentId = (int)$this->getResourceId($response);
        /** @var Attachment $attachment */
        $attachment = $this->getEntityManager()->find(Attachment::class, $attachmentId);
        self::assertEquals('Comment for test attachment', $attachment->getComment());
        self::assertEquals($department1Id, $this->getTargetId($attachment));
    }

    public function testTryToCreateWithIncludedFileContentNotAllowedForTargetEntity(): void
    {
        $response = $this->post(
            ['entity' => 'attachments'],
            [
                'data' => [
                    'type' => 'attachments',
                    'relationships' => [
                        'file' => [
                            'data' => ['type' => 'files', 'id' => 'file'],
                        ],
                        'target' => [
                            'data' => ['type' => 'testapidepartments', 'id' => '<toString(@department1->id)>'],
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'files',
                        'id' => 'file',
                        'attributes' => [
                            'originalFilename' => 'test.txt',
                            'content' => base64_encode('plain text content'),
                        ]
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'file constraint',
                'detail' => sprintf(self::INVALID_MIME_TYPE_MESSAGE, 'text/plain'),
                'source' => ['pointer' => '/data/relationships/file/data']
            ],
            $response
        );
    }

    public function testCreateWithIncludedFileContentAllowedForTargetEntityOnly(): void
    {
        $response = $this->post(
            ['entity' => 'attachments'],
            [
                'data' => [
                    'type' => 'attachments',
                    'relationships' => [
                        'file' => [
                            'data' => ['type' => 'files', 'id' => 'file'],
                        ],
                        'target' => [
                            'data' => ['type' => 'testapidepartments', 'id' => '<toString(@department1->id)>'],
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'files',
                        'id' => 'file',
                        'attributes' => [
                            'originalFilename' => 'image.svg',
                            'content' => base64_encode(file_get_contents(self::SVG_FILE_PATH)),
                        ]
                    ]
                ]
            ]
        );

        $attachment = $this->getEntityManager()->find(Attachment::class, (int)$this->getResourceId($response));

        self::assertSame('image/svg+xml', $attachment->getFile()->getMimeType());
        self::assertSame(Attachment::class, $attachment->getFile()->getParentEntityClass());
        self::assertSame($attachment->getId(), $attachment->getFile()->getParentEntityId());
        self::assertSame('file', $attachment->getFile()->getParentEntityFieldName());
    }

    public function testTryToCreateWithIncludedExternalFileNotAllowedForTargetEntity(): void
    {
        $this->setConfigValue(self::ATTACHMENT_EXTERNAL_FILE_URLS_REGEXP, '^http://example\\.org');

        $response = $this->post(
            ['entity' => 'attachments'],
            [
                'data' => [
                    'type' => 'attachments',
                    'relationships' => [
                        'file' => [
                            'data' => ['type' => 'files', 'id' => 'file'],
                        ],
                        'target' => [
                            'data' => ['type' => 'testapidepartments', 'id' => '<toString(@department1->id)>'],
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'files',
                        'id' => 'file',
                        'attributes' => ['externalUrl' => ExternalFileFactoryStub::FILE_A_TEST_URL]
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'external file mime type constraint',
                'detail' => sprintf(self::INVALID_MIME_TYPE_MESSAGE, 'text/plain'),
                'source' => ['pointer' => '/data/relationships/file/data']
            ],
            $response
        );
    }

    public function testCreateWithIncludedExternalFileAllowedForTargetEntity(): void
    {
        $this->setConfigValue(self::ATTACHMENT_EXTERNAL_FILE_URLS_REGEXP, '^http://example\\.org');

        $response = $this->post(
            ['entity' => 'attachments'],
            [
                'data' => [
                    'type' => 'attachments',
                    'relationships' => [
                        'file' => [
                            'data' => ['type' => 'files', 'id' => 'file'],
                        ],
                        'target' => [
                            'data' => ['type' => 'testapidepartments', 'id' => '<toString(@department1->id)>'],
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'files',
                        'id' => 'file',
                        'attributes' => ['externalUrl' => ExternalFileFactoryStub::IMAGE_A_TEST_URL]
                    ]
                ]
            ]
        );

        $attachment = $this->getEntityManager()->find(Attachment::class, (int)$this->getResourceId($response));

        self::assertSame(ExternalFileFactoryStub::IMAGE_A_TEST_URL, $attachment->getFile()->getExternalUrl());
    }

    public function testTryToCreateWithIncludedFileWithParentIsAnotherAttachment(): void
    {
        /** @var Attachment $anotherAttachment */
        $anotherAttachment = $this->getReference('attachment3');
        $anotherAttachmentId = $anotherAttachment->getId();
        $anotherAttachmentFileId = $anotherAttachment->getFile()->getId();

        $response = $this->post(
            ['entity' => 'attachments'],
            [
                'data' => [
                    'type' => 'attachments',
                    'relationships' => [
                        'file' => [
                            'data' => ['type' => 'files', 'id' => 'file'],
                        ],
                        'target' => [
                            'data' => ['type' => 'testapidepartments', 'id' => '<toString(@department1->id)>'],
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'files',
                        'id' => 'file',
                        'attributes' => [
                            'originalFilename' => 'blank.png',
                            'content' => self::BLANK_FILE_CONTENT,
                            'parentFieldName' => 'file',
                        ],
                        'relationships' => [
                            'parent' => [
                                'data' => ['type' => 'attachments', 'id' => (string)$anotherAttachmentId],
                            ],
                        ]
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'form constraint',
                'detail' => 'The parent of a new file should be omitted or should be the entity and the field'
                    . ' the file is added to.',
                'source' => ['pointer' => '/data/relationships/file/data']
            ],
            $response
        );

        $em = $this->getEntityManager();
        $em->clear();
        $anotherAttachment = $em->find(Attachment::class, $anotherAttachmentId);
        self::assertSame($anotherAttachmentFileId, $anotherAttachment->getFile()->getId());
        self::assertNotNull($em->find(File::class, $anotherAttachmentFileId));
    }

    public function testTryToCreateWithoutTarget(): void
    {
        $response = $this->post(
            ['entity' => 'attachments'],
            [
                'data' => [
                    'type'          => 'attachments',
                    'attributes'    => [
                        'comment' => 'Comment for test attachment'
                    ],
                    'relationships' => [
                        'file' => [
                            'data' => ['type' => 'files', 'id' => 'file']
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type'          => 'files',
                        'id'            => 'file',
                        'attributes' => [
                            'mimeType'         => 'image/png',
                            'originalFilename' => 'blank.png',
                            'content'          => self::BLANK_FILE_CONTENT
                        ]
                    ]
                ]
            ],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/relationships/target/data']
            ],
            $response
        );
    }

    public function testTryToCreateWithNullTarget(): void
    {
        $response = $this->post(
            ['entity' => 'attachments'],
            [
                'data' => [
                    'type'          => 'attachments',
                    'attributes'    => [
                        'comment' => 'Comment for test attachment'
                    ],
                    'relationships' => [
                        'file'   => [
                            'data' => ['type' => 'files', 'id' => 'file']
                        ],
                        'target' => [
                            'data' => null
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type'          => 'files',
                        'id'            => 'file',
                        'attributes' => [
                            'mimeType'         => 'image/png',
                            'originalFilename' => 'blank.png',
                            'content'          => self::BLANK_FILE_CONTENT
                        ]
                    ]
                ]
            ],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/relationships/target/data']
            ],
            $response
        );
    }

    public function testTryToCreateWithoutFile(): void
    {
        $response = $this->post(
            ['entity' => 'attachments'],
            [
                'data' => [
                    'type'          => 'attachments',
                    'attributes'    => [
                        'comment' => 'Comment for test attachment'
                    ],
                    'relationships' => [
                        'target' => [
                            'data' => ['type' => 'testapidepartments', 'id' => '<toString(@department1->id)>']
                        ]
                    ]
                ]
            ],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/relationships/file/data']
            ],
            $response
        );
    }

    public function testTryToCreateWithNullFile(): void
    {
        $response = $this->post(
            ['entity' => 'attachments'],
            [
                'data' => [
                    'type'          => 'attachments',
                    'attributes'    => [
                        'comment' => 'Comment for test attachment'
                    ],
                    'relationships' => [
                        'file'   => [
                            'data' => null
                        ],
                        'target' => [
                            'data' => ['type' => 'testapidepartments', 'id' => '<toString(@department1->id)>']
                        ]
                    ]
                ]
            ],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/relationships/file/data']
            ],
            $response
        );
    }

    public function testUpdate(): void
    {
        $attachmentId = $this->getReference('attachment1')->getId();
        $department31Id = $this->getReference('department3')->getId();
        $data = [
            'data' => [
                'type'          => 'attachments',
                'id'            => (string)$attachmentId,
                'attributes'    => [
                    'comment' => 'New comment for test attachment'
                ],
                'relationships' => [
                    'target' => [
                        'data' => ['type' => 'testapidepartments', 'id' => '<toString(@department3->id)>']
                    ]
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => 'attachments', 'id' => (string)$attachmentId],
            $data
        );
        $this->assertResponseContains($data, $response);

        /** @var Attachment $attachment */
        $attachment = $this->getEntityManager()->find(Attachment::class, $attachmentId);
        self::assertEquals('New comment for test attachment', $attachment->getComment());
        self::assertEquals($department31Id, $this->getTargetId($attachment));
    }

    public function testTryToUpdateTargetToNull(): void
    {
        $response = $this->patch(
            ['entity' => 'attachments', 'id' => '<toString(@attachment1->id)>'],
            [
                'data' => [
                    'type'          => 'attachments',
                    'id'            => '<toString(@attachment1->id)>',
                    'relationships' => [
                        'target' => [
                            'data' => null
                        ]
                    ]
                ]
            ],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/relationships/target/data']
            ],
            $response
        );
    }

    public function testTryToUpdateFileToNull(): void
    {
        $response = $this->patch(
            ['entity' => 'attachments', 'id' => '<toString(@attachment1->id)>'],
            [
                'data' => [
                    'type'          => 'attachments',
                    'id'            => '<toString(@attachment1->id)>',
                    'relationships' => [
                        'file' => [
                            'data' => null
                        ]
                    ]
                ]
            ],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/relationships/file/data']
            ],
            $response
        );
    }

    public function testDelete(): void
    {
        $attachmentId = $this->getReference('attachment1')->getId();
        $this->delete(
            ['entity' => 'attachments', 'id' => (string)$attachmentId]
        );
        self::assertTrue(null === $this->getEntityManager()->find(Attachment::class, $attachmentId));
    }

    public function testGetSubresourceForTarget(): void
    {
        $response = $this->getSubresource(
            ['entity' => 'attachments', 'id' => '<toString(@attachment1->id)>', 'association' => 'target']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'testapidepartments',
                    'id'         => '<toString(@department1->id)>',
                    'attributes' => [
                        'title' => '<toString(@department1->name)>'
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetSubresourceForTargetWithIncludeFilter(): void
    {
        $response = $this->getSubresource(
            ['entity' => 'attachments', 'id' => '<toString(@attachment1->id)>', 'association' => 'target'],
            ['include' => 'organization']
        );
        $this->assertResponseContains(
            [
                'data'     => [
                    'type'       => 'testapidepartments',
                    'id'         => '<toString(@department1->id)>',
                    'attributes' => [
                        'title' => '<toString(@department1->name)>'
                    ]
                ],
                'included' => [
                    [
                        'type'       => 'organizations',
                        'id'         => '<toString(@organization->id)>',
                        'attributes' => [
                            'name' => '<toString(@organization->name)>'
                        ]
                    ]
                ]
            ],
            $response
        );
        $responseContent = self::jsonToArray($response->getContent());
        self::assertCount(1, $responseContent['included']);
    }

    public function testGetRelationshipForTarget(): void
    {
        $response = $this->getRelationship(
            ['entity' => 'attachments', 'id' => '<toString(@attachment1->id)>', 'association' => 'target']
        );
        $this->assertResponseContains(
            ['data' => ['type' => 'testapidepartments', 'id' => '<toString(@department1->id)>']],
            $response
        );
        $responseContent = self::jsonToArray($response->getContent());
        self::assertArrayNotHasKey('attributes', $responseContent['data']);
        self::assertArrayNotHasKey('relationships', $responseContent['data']);
    }

    public function testUpdateRelationshipForTarget(): void
    {
        $attachmentId = $this->getReference('attachment1')->getId();
        $department2Id = $this->getReference('department2')->getId();
        $this->patchRelationship(
            ['entity' => 'attachments', 'id' => (string)$attachmentId, 'association' => 'target'],
            ['data' => ['type' => 'testapidepartments', 'id' => (string)$department2Id]]
        );
        /** @var Attachment $attachment */
        $attachment = $this->getEntityManager()->find(Attachment::class, $attachmentId);
        self::assertEquals($department2Id, $this->getTargetId($attachment));
    }

    public function testTryToUpdateRelationshipForTargetToNull()
    {
        $response = $this->patchRelationship(
            ['entity' => 'attachments', 'id' => '<toString(@attachment1->id)>', 'association' => 'target'],
            ['data' => null],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'not blank constraint',
                'detail' => 'This value should not be blank.'
            ],
            $response
        );
    }

    public function testGetSubresourceForFile(): void
    {
        $response = $this->getSubresource(
            ['entity' => 'attachments', 'id' => '<toString(@attachment1->id)>', 'association' => 'file']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'files',
                    'id'         => '<toString(@file1->id)>',
                    'attributes' => [
                        'mimeType' => '<toString(@file1->mimeType)>'
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetRelationshipForFile(): void
    {
        $response = $this->getRelationship(
            ['entity' => 'attachments', 'id' => '<toString(@attachment1->id)>', 'association' => 'file']
        );
        $this->assertResponseContains(
            ['data' => ['type' => 'files', 'id' => '<toString(@file1->id)>']],
            $response
        );
        $responseContent = self::jsonToArray($response->getContent());
        self::assertArrayNotHasKey('attributes', $responseContent['data']);
        self::assertArrayNotHasKey('relationships', $responseContent['data']);
    }

    public function testUpdateRelationshipForFile(): void
    {
        $response = $this->patchRelationship(
            ['entity' => 'attachments', 'id' => '<toString(@attachment1->id)>', 'association' => 'file'],
            ['data' => ['type' => 'files', 'id' => '<toString(@file1->id)>']],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }
}
