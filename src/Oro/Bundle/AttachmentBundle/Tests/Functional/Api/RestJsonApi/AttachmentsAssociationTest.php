<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestDepartment;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\AttachmentBundle\Entity\Attachment;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * @dbIsolationPerTest
 */
class AttachmentsAssociationTest extends RestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            '@OroAttachmentBundle/Tests/Functional/Api/DataFixtures/attachment_data.yml'
        ]);
    }

    private function getAttachmentIds(int $departmentId): array
    {
        $rows = $this->getEntityManager()->createQueryBuilder()
            ->from(Attachment::class, 'c')
            ->select('c.id')
            ->join('c.' . ExtendHelper::buildAssociationName(TestDepartment::class), 'n')
            ->where('n.id = :departmentId')
            ->setParameter('departmentId', $departmentId)
            ->orderBy('c.id')
            ->getQuery()
            ->getArrayResult();

        return array_column($rows, 'id');
    }

    public function testGet(): void
    {
        $response = $this->get(
            ['entity' => 'testapidepartments', 'id' => '<toString(@department1->id)>']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => 'testapidepartments',
                    'id'            => '<toString(@department1->id)>',
                    'relationships' => [
                        'attachments' => [
                            'data' => [
                                ['type' => 'attachments', 'id' => '<toString(@attachment1->id)>'],
                                ['type' => 'attachments', 'id' => '<toString(@attachment2->id)>']
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetSubresourceForAttachments(): void
    {
        $response = $this->getSubresource(
            ['entity' => 'testapidepartments', 'id' => '<toString(@department1->id)>', 'association' => 'attachments']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'attachments',
                        'id'         => '<toString(@attachment1->id)>',
                        'attributes' => [
                            'comment' => '<toString(@attachment1->comment)>'
                        ]
                    ],
                    [
                        'type'       => 'attachments',
                        'id'         => '<toString(@attachment2->id)>',
                        'attributes' => [
                            'comment' => '<toString(@attachment2->comment)>'
                        ]
                    ]
                ]
            ],
            $response,
            true
        );
    }

    public function testGetSubresourceForAttachmentsWithIncludeFilter(): void
    {
        $response = $this->getSubresource(
            ['entity' => 'testapidepartments', 'id' => '<toString(@department1->id)>', 'association' => 'attachments'],
            ['include' => 'organization']
        );
        $this->assertResponseContains(
            [
                'data'     => [
                    [
                        'type'       => 'attachments',
                        'id'         => '<toString(@attachment1->id)>',
                        'attributes' => [
                            'comment' => '<toString(@attachment1->comment)>'
                        ]
                    ],
                    [
                        'type'       => 'attachments',
                        'id'         => '<toString(@attachment2->id)>',
                        'attributes' => [
                            'comment' => '<toString(@attachment2->comment)>'
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
                    ]
                ]
            ],
            $response,
            true
        );
    }

    public function testGetRelationshipForAttachments(): void
    {
        $response = $this->getRelationship(
            ['entity' => 'testapidepartments', 'id' => '<toString(@department1->id)>', 'association' => 'attachments']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'attachments', 'id' => '<toString(@attachment1->id)>'],
                    ['type' => 'attachments', 'id' => '<toString(@attachment2->id)>']
                ]
            ],
            $response,
            true
        );
    }

    public function testTryToUpdateRelationshipForAttachments(): void
    {
        $response = $this->patchRelationship(
            ['entity' => 'testapidepartments', 'id' => '<toString(@department1->id)>', 'association' => 'attachments'],
            [
                'data' => [
                    ['type' => 'attachments', 'id' => '<toString(@attachment1->id)>']
                ]
            ],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToAddRelationshipForAttachments(): void
    {
        $response = $this->postRelationship(
            ['entity' => 'testapidepartments', 'id' => '<toString(@department1->id)>', 'association' => 'attachments'],
            [
                'data' => [
                    ['type' => 'attachments', 'id' => '<toString(@attachment1->id)>']
                ]
            ],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDeleteRelationshipForAttachments(): void
    {
        $response = $this->deleteRelationship(
            ['entity' => 'testapidepartments', 'id' => '<toString(@department1->id)>', 'association' => 'attachments'],
            [
                'data' => [
                    ['type' => 'attachments', 'id' => '<toString(@attachment1->id)>']
                ]
            ],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testCreateWithNewAttachments(): void
    {
        $response = $this->post(
            ['entity' => 'testapidepartments'],
            [
                'data'     => [
                    'type'          => 'testapidepartments',
                    'attributes'    => [
                        'title' => 'New department 1'
                    ],
                    'relationships' => [
                        'attachments' => [
                            'data' => [
                                ['type' => 'attachments', 'id' => 'attachment1']
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type'          => 'attachments',
                        'id'            => 'attachment1',
                        'attributes'    => [
                            'comment' => 'Attachment for new department 1'
                        ],
                        'relationships' => [
                            'file' => [
                                'data' => ['type' => 'files', 'id' => '<toString(@file4->id)>']
                            ]
                        ]
                    ]
                ]
            ]
        );

        $departmentId = (int)$this->getResourceId($response);
        /** @var TestDepartment $department */
        $department = $this->getEntityManager()->find(TestDepartment::class, $departmentId);
        self::assertEquals('New department 1', $department->getName());
        $attachmentIds = $this->getAttachmentIds($departmentId);
        self::assertCount(1, $attachmentIds);
        /** @var Attachment $attachment */
        $attachment = $this->getEntityManager()->find(Attachment::class, $attachmentIds[0]);
        self::assertEquals('Attachment for new department 1', $attachment->getComment());
    }

    public function testCreateWithExistingAttachments(): void
    {
        $response = $this->post(
            ['entity' => 'testapidepartments'],
            [
                'data' => [
                    'type'          => 'testapidepartments',
                    'attributes'    => [
                        'title' => 'New department 2'
                    ],
                    'relationships' => [
                        'attachments' => [
                            'data' => [
                                ['type' => 'attachments', 'id' => '<toString(@attachment3->id)>']
                            ]
                        ]
                    ]
                ]
            ]
        );

        $departmentId = (int)$this->getResourceId($response);
        /** @var TestDepartment $department */
        $department = $this->getEntityManager()->find(TestDepartment::class, $departmentId);
        self::assertEquals('New department 2', $department->getName());
        $attachmentIds = $this->getAttachmentIds($departmentId);
        self::assertCount(1, $attachmentIds);
        /** @var Attachment $attachment */
        $attachment = $this->getEntityManager()->find(Attachment::class, $attachmentIds[0]);
        self::assertEquals('Attachment 3', $attachment->getComment());
    }

    public function testTryToUpdateAttachments(): void
    {
        $departmentId = $this->getReference('department2')->getId();
        $attachment3Id = $this->getReference('attachment3')->getId();
        $this->patch(
            ['entity' => 'testapidepartments', 'id' => (string)$departmentId],
            [
                'data' => [
                    'type'          => 'testapidepartments',
                    'id'            => (string)$departmentId,
                    'attributes'    => [
                        'title' => 'New department 2'
                    ],
                    'relationships' => [
                        'attachments' => [
                            'data' => [
                                ['type' => 'attachments', 'id' => '<toString(@attachment2->id)>']
                            ]
                        ]
                    ]
                ]
            ]
        );

        self::assertEquals([$attachment3Id], $this->getAttachmentIds($departmentId));
    }
}
