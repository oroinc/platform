<?php

namespace Oro\Bundle\NoteBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\NoteBundle\Entity\Note;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivityTarget;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class NoteTest extends RestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures(['@OroNoteBundle/Tests/Functional/Api/DataFixtures/notes.yml']);
    }

    private function getActivityTargetIds(Note $note, string $targetClass): array
    {
        $result = [];
        $targets = $note->getActivityTargets($targetClass);
        foreach ($targets as $target) {
            $result[] = $target->getId();
        }
        sort($result);

        return $result;
    }

    public function testGetList(): void
    {
        $response = $this->cget(['entity' => 'notes']);
        $this->assertResponseContains('cget_note.yml', $response);
        self::assertArrayNotHasKey('included', self::jsonToArray($response->getContent()));
    }

    public function testGetListWithActivityTargetsInIncludeFilter(): void
    {
        $response = $this->cget(
            ['entity' => 'notes'],
            ['include' => 'activityTargets,organization']
        );
        $this->assertResponseContains('cget_note_include.yml', $response);
        $responseContent = self::jsonToArray($response->getContent());
        self::assertCount(3, $responseContent['included'], 'included');
    }

    public function testGet(): void
    {
        $response = $this->get(['entity' => 'notes', 'id' => '<toString(@note1->id)>']);
        $this->assertResponseContains('get_note.yml', $response);
    }

    public function testCreate(): void
    {
        $userId = $this->getReference('user')->getId();
        $targetEntity1Id = $this->getReference('targetEntity1')->getId();

        $response = $this->post(
            ['entity' => 'notes'],
            [
                'data' => [
                    'type'          => 'notes',
                    'attributes'    => [
                        'message'     => 'New note'
                    ],
                    'relationships' => [
                        'activityTargets' => [
                            'data' => [
                                ['type' => 'testactivitytargets', 'id' => (string)$targetEntity1Id]
                            ]
                        ]
                    ]
                ]
            ]
        );

        $noteId = (int)$this->getResourceId($response);
        /** @var Note $note */
        $note = $this->getEntityManager()->find(Note::class, $noteId);
        self::assertEquals('New note', $note->getMessage());
        self::assertEquals($userId, $note->getUpdatedBy()->getId());
        self::assertEquals([$targetEntity1Id], $this->getActivityTargetIds($note, TestActivityTarget::class));
    }

    public function testTryToCreateWithUpdatedBy(): void
    {
        $user1Id = $this->getReference('user1')->getId();
        $targetEntity1Id = $this->getReference('targetEntity1')->getId();

        $response = $this->post(
            ['entity' => 'notes'],
            [
                'data' => [
                    'type'          => 'notes',
                    'attributes'    => [
                        'message'     => 'New note'
                    ],
                    'relationships' => [
                        'activityTargets' => [
                            'data' => [
                                ['type' => 'testactivitytargets', 'id' => (string)$targetEntity1Id]
                            ]
                        ],
                        'updatedBy' => [
                            'data' => ['type' => 'users', 'id' => (string)$user1Id]
                        ]
                    ]
                ]
            ]
        );

        $noteId = (int)$this->getResourceId($response);
        /** @var Note $note */
        $note = $this->getEntityManager()->find(Note::class, $noteId);
        self::assertEquals('New note', $note->getMessage());
        self::assertEquals($user1Id, $note->getUpdatedBy()->getId());
        self::assertEquals([$targetEntity1Id], $this->getActivityTargetIds($note, TestActivityTarget::class));
    }

    public function testUpdate(): void
    {
        $noteId = $this->getReference('note1')->getId();
        $userId = $this->getReference('user')->getId();
        $targetEntity2Id = $this->getReference('targetEntity2')->getId();
        $data = [
            'data' => [
                'type'          => 'notes',
                'id'            => (string)$noteId,
                'attributes'    => [
                    'message'     => 'Updated note'
                ],
                'relationships' => [
                    'activityTargets' => [
                        'data' => [
                            ['type' => 'testactivitytargets', 'id' => (string)$targetEntity2Id]
                        ]
                    ]
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => 'notes', 'id' => (string)$noteId],
            $data
        );
        $this->assertResponseContains($data, $response);

        /** @var Note $note */
        $note = $this->getEntityManager()->find(Note::class, $noteId);
        self::assertEquals('Updated note', $note->getMessage());
        self::assertEquals($userId, $note->getUpdatedBy()->getId());
        self::assertEquals([$targetEntity2Id], $this->getActivityTargetIds($note, TestActivityTarget::class));
    }

    public function testUpdateUpdatedBy(): void
    {
        $noteId = $this->getReference('note3')->getId();
        $user1Id = $this->getReference('user1')->getId();
        $data = [
            'data' => [
                'type'          => 'notes',
                'id'            => (string)$noteId,
                'attributes'    => [
                    'message'     => 'Updated note'
                ],
                'relationships' => [
                    'updatedBy' => [
                        'data' => ['type' => 'users', 'id' => (string)$user1Id]
                    ]
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => 'notes', 'id' => (string)$noteId],
            $data
        );
        $this->assertResponseContains($data, $response);

        /** @var Note $note */
        $note = $this->getEntityManager()->find(Note::class, $noteId);
        self::assertEquals('Updated note', $note->getMessage());
        self::assertEquals($user1Id, $note->getUpdatedBy()->getId());
    }

    public function testDelete(): void
    {
        $noteId = $this->getReference('note1')->getId();
        $this->delete(
            ['entity' => 'notes', 'id' => (string)$noteId]
        );
        self::assertTrue(null === $this->getEntityManager()->find(Note::class, $noteId));
    }

    public function testGetSubresourceForUpdatedBy(): void
    {
        $noteUpdatedByUserName = $this->getReference('note1')->getUpdatedBy()->getUsername();
        $response = $this->getSubresource(
            ['entity' => 'notes', 'id' => '<toString(@note1->id)>', 'association' => 'updatedBy']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'users',
                    'id'         => '<toString(@note1->updatedBy->id)>',
                    'attributes' => [
                        'username' => $noteUpdatedByUserName
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetRelationshipForUpdatedBy(): void
    {
        $response = $this->getRelationship(
            ['entity' => 'notes', 'id' => '<toString(@note1->id)>', 'association' => 'updatedBy']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'users',
                    'id'   => '<toString(@note1->updatedBy->id)>'
                ]
            ],
            $response
        );
    }

    public function testUpdateRelationshipForUpdatedBy(): void
    {
        $noteId = $this->getReference('note3')->getId();
        $user1Id = $this->getReference('user1')->getId();
        $this->patchRelationship(
            ['entity' => 'notes', 'id' => (string)$noteId, 'association' => 'updatedBy'],
            [
                'data' => [
                    'type' => 'users',
                    'id'   => (string)$user1Id
                ]
            ]
        );
        /** @var Note $note */
        $note = $this->getEntityManager()->find(Note::class, $noteId);
        self::assertEquals($user1Id, $note->getUpdatedBy()->getId());
    }

    public function testGetSubresourceForActivityTargets(): void
    {
        $response = $this->getSubresource(
            ['entity' => 'notes', 'id' => '<toString(@note2->id)>', 'association' => 'activityTargets']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'testactivitytargets',
                        'id'         => '<toString(@targetEntity1->id)>',
                        'attributes' => [
                            'name' => '<toString(@targetEntity1->string)>'
                        ]
                    ],
                    [
                        'type'       => 'testactivitytargets',
                        'id'         => '<toString(@targetEntity2->id)>',
                        'attributes' => [
                            'name' => '<toString(@targetEntity2->string)>'
                        ]
                    ]
                ]
            ],
            $response,
            true
        );
    }

    public function testGetRelationshipForActivityTargets(): void
    {
        $response = $this->getRelationship(
            ['entity' => 'notes', 'id' => '<toString(@note2->id)>', 'association' => 'activityTargets']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'testactivitytargets', 'id' => '<toString(@targetEntity1->id)>'],
                    ['type' => 'testactivitytargets', 'id' => '<toString(@targetEntity2->id)>']
                ]
            ],
            $response,
            true
        );
    }

    public function testUpdateRelationshipForActivityTargets(): void
    {
        $noteId = $this->getReference('note1')->getId();
        $userId = $this->getReference('user')->getId();
        $targetEntity2Id = $this->getReference('targetEntity2')->getId();
        $this->patchRelationship(
            ['entity' => 'notes', 'id' => (string)$noteId, 'association' => 'activityTargets'],
            [
                'data' => [
                    ['type' => 'testactivitytargets', 'id' => (string)$targetEntity2Id]
                ]
            ]
        );
        /** @var Note $note */
        $note = $this->getEntityManager()->find(Note::class, $noteId);
        self::assertEquals($userId, $note->getUpdatedBy()->getId());
        self::assertEquals([$targetEntity2Id], $this->getActivityTargetIds($note, TestActivityTarget::class));
    }

    public function testAddRelationshipForActivityTargets(): void
    {
        $noteId = $this->getReference('note1')->getId();
        $userId = $this->getReference('user')->getId();
        $targetEntity1Id = $this->getReference('targetEntity1')->getId();
        $targetEntity2Id = $this->getReference('targetEntity2')->getId();
        $this->postRelationship(
            ['entity' => 'notes', 'id' => (string)$noteId, 'association' => 'activityTargets'],
            [
                'data' => [
                    ['type' => 'testactivitytargets', 'id' => (string)$targetEntity2Id]
                ]
            ]
        );
        /** @var Note $note */
        $note = $this->getEntityManager()->find(Note::class, $noteId);
        self::assertEquals($userId, $note->getUpdatedBy()->getId());
        self::assertEquals(
            [$targetEntity1Id, $targetEntity2Id],
            $this->getActivityTargetIds($note, TestActivityTarget::class)
        );
    }

    public function testDeleteRelationshipForActivityTargets(): void
    {
        $noteId = $this->getReference('note2')->getId();
        $userId = $this->getReference('user')->getId();
        $targetEntity1Id = $this->getReference('targetEntity1')->getId();
        $targetEntity2Id = $this->getReference('targetEntity2')->getId();
        $this->deleteRelationship(
            ['entity' => 'notes', 'id' => (string)$noteId, 'association' => 'activityTargets'],
            [
                'data' => [
                    ['type' => 'testactivitytargets', 'id' => (string)$targetEntity1Id]
                ]
            ]
        );
        /** @var Note $note */
        $note = $this->getEntityManager()->find(Note::class, $noteId);
        self::assertEquals($userId, $note->getUpdatedBy()->getId());
        self::assertEquals([$targetEntity2Id], $this->getActivityTargetIds($note, TestActivityTarget::class));
    }
}
