<?php

namespace Oro\Bundle\NoteBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ActivityBundle\EntityConfig\ActivityScope;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\NoteBundle\Entity\Note;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivityTarget;

class NoteActivityTest extends RestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures(['@OroNoteBundle/Tests/Functional/Api/DataFixtures/notes.yml']);
    }

    private function getActivityNoteIds(int $targetEntityId): array
    {
        $rows = $this->getEntityManager()->createQueryBuilder()
            ->from(Note::class, 't')
            ->select('t.id')
            ->join(
                't.' . ExtendHelper::buildAssociationName(TestActivityTarget::class, ActivityScope::ASSOCIATION_KIND),
                'c'
            )
            ->where('c.id = :targetEntityId')
            ->setParameter('targetEntityId', $targetEntityId)
            ->orderBy('t.id')
            ->getQuery()
            ->getArrayResult();

        return array_column($rows, 'id');
    }

    public function testGet(): void
    {
        $response = $this->get(
            ['entity' => 'testactivitytargets', 'id' => '<toString(@targetEntity1->id)>']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => 'testactivitytargets',
                    'id'            => '<toString(@targetEntity1->id)>',
                    'relationships' => [
                        'activityNotes' => [
                            'data' => [
                                ['type' => 'notes', 'id' => '<toString(@note1->id)>'],
                                ['type' => 'notes', 'id' => '<toString(@note2->id)>']
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetSubresourceForActivityNotes(): void
    {
        $response = $this->getSubresource([
            'entity'      => 'testactivitytargets',
            'id'          => '<toString(@targetEntity1->id)>',
            'association' => 'activityNotes'
        ]);
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'notes',
                        'id'         => '<toString(@note1->id)>',
                        'attributes' => [
                            'message' => '<toString(@note1->message)>'
                        ]
                    ],
                    [
                        'type'       => 'notes',
                        'id'         => '<toString(@note2->id)>',
                        'attributes' => [
                            'message' => '<toString(@note2->message)>'
                        ]
                    ]
                ]
            ],
            $response,
            true
        );
    }

    public function testGetRelationshipForActivityNotes(): void
    {
        $response = $this->getRelationship([
            'entity'      => 'testactivitytargets',
            'id'          => '<toString(@targetEntity1->id)>',
            'association' => 'activityNotes'
        ]);
        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'notes', 'id' => '<toString(@note1->id)>'],
                    ['type' => 'notes', 'id' => '<toString(@note2->id)>']
                ]
            ],
            $response,
            true
        );
    }

    public function testUpdateRelationshipForActivityNotes(): void
    {
        $targetEntityId = $this->getReference('targetEntity2')->getId();
        $note2Id = $this->getReference('note2')->getId();
        $this->patchRelationship(
            ['entity' => 'testactivitytargets', 'id' => (string)$targetEntityId, 'association' => 'activityNotes'],
            [
                'data' => [
                    ['type' => 'notes', 'id' => (string)$note2Id]
                ]
            ]
        );
        self::assertEquals([$note2Id], $this->getActivityNoteIds($targetEntityId));
    }

    public function testAddRelationshipForActivityNotes(): void
    {
        $targetEntityId = $this->getReference('targetEntity2')->getId();
        $note2Id = $this->getReference('note2')->getId();
        $note3Id = $this->getReference('note3')->getId();
        $this->postRelationship(
            ['entity' => 'testactivitytargets', 'id' => (string)$targetEntityId, 'association' => 'activityNotes'],
            [
                'data' => [
                    ['type' => 'notes', 'id' => (string)$note3Id]
                ]
            ]
        );
        self::assertEquals([$note2Id, $note3Id], $this->getActivityNoteIds($targetEntityId));
    }

    public function testDeleteRelationshipForActivityNotes(): void
    {
        $targetEntityId = $this->getReference('targetEntity1')->getId();
        $note1Id = $this->getReference('note1')->getId();
        $note2Id = $this->getReference('note2')->getId();
        $this->deleteRelationship(
            ['entity' => 'testactivitytargets', 'id' => (string)$targetEntityId, 'association' => 'activityNotes'],
            [
                'data' => [
                    ['type' => 'notes', 'id' => (string)$note1Id]
                ]
            ]
        );
        self::assertEquals([$note2Id], $this->getActivityNoteIds($targetEntityId));
    }
}
