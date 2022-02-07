<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\AttachmentBundle\Entity\Attachment;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class AttachmentTest extends RestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            '@OroAttachmentBundle/Tests/Functional/Api/DataFixtures/attachment_data.yml'
        ]);
    }

    private function getTargetId(Attachment $attachment): ?int
    {
        $target = $attachment->getTarget();
        if (null === $target) {
            return null;
        }

        return $target->getId();
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
                            'data' => ['type' => 'files', 'id' => '<toString(@file4->id)>']
                        ],
                        'target' => [
                            'data' => ['type' => 'testapidepartments', 'id' => '<toString(@department1->id)>']
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
                            'data' => ['type' => 'files', 'id' => '<toString(@file4->id)>']
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
                            'data' => ['type' => 'files', 'id' => '<toString(@file4->id)>']
                        ],
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
        $attachmentId = $this->getReference('attachment1')->getId();
        $file4Id = $this->getReference('file2')->getId();
        $this->patchRelationship(
            ['entity' => 'attachments', 'id' => (string)$attachmentId, 'association' => 'file'],
            ['data' => ['type' => 'files', 'id' => (string)$file4Id]]
        );
        /** @var Attachment $attachment */
        $attachment = $this->getEntityManager()->find(Attachment::class, $attachmentId);
        self::assertEquals($file4Id, $attachment->getFile()->getId());
    }

    public function testTryToUpdateRelationshipForFileToNull()
    {
        $response = $this->patchRelationship(
            ['entity' => 'attachments', 'id' => '<toString(@attachment1->id)>', 'association' => 'file'],
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
}
