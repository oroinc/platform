<?php

namespace Oro\Bundle\CommentBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\CommentBundle\Entity\Comment;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\NoteBundle\Entity\Note;

/**
 * @dbIsolationPerTest
 */
class CommentsAssociationTest extends RestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            '@OroCommentBundle/Tests/Functional/Api/DataFixtures/comment_data.yml'
        ]);
    }

    private function getCommentIds(int $noteId): array
    {
        $rows = $this->getEntityManager()->createQueryBuilder()
            ->from(Comment::class, 'c')
            ->select('c.id')
            ->join('c.' . ExtendHelper::buildAssociationName(Note::class), 'n')
            ->where('n.id = :noteId')
            ->setParameter('noteId', $noteId)
            ->orderBy('c.id')
            ->getQuery()
            ->getArrayResult();

        return array_column($rows, 'id');
    }

    public function testGet(): void
    {
        $response = $this->get(
            ['entity' => 'notes', 'id' => '<toString(@note1->id)>']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => 'notes',
                    'id'            => '<toString(@note1->id)>',
                    'relationships' => [
                        'comments' => [
                            'data' => [
                                ['type' => 'comments', 'id' => '<toString(@comment1->id)>'],
                                ['type' => 'comments', 'id' => '<toString(@comment2->id)>']
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetSubresourceForComments(): void
    {
        $response = $this->getSubresource(
            ['entity' => 'notes', 'id' => '<toString(@note1->id)>', 'association' => 'comments']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'comments',
                        'id'         => '<toString(@comment1->id)>',
                        'attributes' => [
                            'message' => '<toString(@comment1->message)>'
                        ]
                    ],
                    [
                        'type'       => 'comments',
                        'id'         => '<toString(@comment2->id)>',
                        'attributes' => [
                            'message' => '<toString(@comment2->message)>'
                        ]
                    ]
                ]
            ],
            $response,
            true
        );
    }

    public function testGetSubresourceForCommentsWithIncludeFilter(): void
    {
        $response = $this->getSubresource(
            ['entity' => 'notes', 'id' => '<toString(@note1->id)>', 'association' => 'comments'],
            ['include' => 'organization']
        );
        $this->assertResponseContains(
            [
                'data'     => [
                    [
                        'type'       => 'comments',
                        'id'         => '<toString(@comment1->id)>',
                        'attributes' => [
                            'message' => '<toString(@comment1->message)>'
                        ]
                    ],
                    [
                        'type'       => 'comments',
                        'id'         => '<toString(@comment2->id)>',
                        'attributes' => [
                            'message' => '<toString(@comment2->message)>'
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

    public function testGetRelationshipForComments(): void
    {
        $response = $this->getRelationship(
            ['entity' => 'notes', 'id' => '<toString(@note1->id)>', 'association' => 'comments']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'comments', 'id' => '<toString(@comment1->id)>'],
                    ['type' => 'comments', 'id' => '<toString(@comment2->id)>']
                ]
            ],
            $response,
            true
        );
    }

    public function testTryToUpdateRelationshipForComments(): void
    {
        $response = $this->patchRelationship(
            ['entity' => 'notes', 'id' => '<toString(@note1->id)>', 'association' => 'comments'],
            [
                'data' => [
                    ['type' => 'comments', 'id' => '<toString(@comment1->id)>']
                ]
            ],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToAddRelationshipForComments(): void
    {
        $response = $this->postRelationship(
            ['entity' => 'notes', 'id' => '<toString(@note1->id)>', 'association' => 'comments'],
            [
                'data' => [
                    ['type' => 'comments', 'id' => '<toString(@comment1->id)>']
                ]
            ],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDeleteRelationshipForComments(): void
    {
        $response = $this->deleteRelationship(
            ['entity' => 'notes', 'id' => '<toString(@note1->id)>', 'association' => 'comments'],
            [
                'data' => [
                    ['type' => 'comments', 'id' => '<toString(@comment1->id)>']
                ]
            ],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testCreateWithNewComments(): void
    {
        $response = $this->post(
            ['entity' => 'notes'],
            [
                'data'     => [
                    'type'          => 'notes',
                    'attributes'    => [
                        'message' => 'New note 1'
                    ],
                    'relationships' => [
                        'comments' => [
                            'data' => [
                                ['type' => 'comments', 'id' => 'comment1']
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type'       => 'comments',
                        'id'         => 'comment1',
                        'attributes' => [
                            'message' => 'Comment for new note 1'
                        ]
                    ]
                ]
            ]
        );

        $noteId = (int)$this->getResourceId($response);
        /** @var Note $note */
        $note = $this->getEntityManager()->find(Note::class, $noteId);
        self::assertEquals('New note 1', $note->getMessage());
        $commentIds = $this->getCommentIds($noteId);
        self::assertCount(1, $commentIds);
        /** @var Comment $comment */
        $comment = $this->getEntityManager()->find(Comment::class, $commentIds[0]);
        self::assertEquals('Comment for new note 1', $comment->getMessage());
    }

    public function testCreateWithExistingComments(): void
    {
        $response = $this->post(
            ['entity' => 'notes'],
            [
                'data' => [
                    'type'          => 'notes',
                    'attributes'    => [
                        'message' => 'New note 2'
                    ],
                    'relationships' => [
                        'comments' => [
                            'data' => [
                                ['type' => 'comments', 'id' => '<toString(@comment3->id)>']
                            ]
                        ]
                    ]
                ]
            ]
        );

        $noteId = (int)$this->getResourceId($response);
        /** @var Note $note */
        $note = $this->getEntityManager()->find(Note::class, $noteId);
        self::assertEquals('New note 2', $note->getMessage());
        $commentIds = $this->getCommentIds($noteId);
        self::assertCount(1, $commentIds);
        /** @var Comment $comment */
        $comment = $this->getEntityManager()->find(Comment::class, $commentIds[0]);
        self::assertEquals('Comment 3', $comment->getMessage());
    }

    public function testTryToUpdateComments(): void
    {
        $noteId = $this->getReference('note2')->getId();
        $comment3Id = $this->getReference('comment3')->getId();
        $this->patch(
            ['entity' => 'notes', 'id' => (string)$noteId],
            [
                'data' => [
                    'type'          => 'notes',
                    'id'            => (string)$noteId,
                    'attributes'    => [
                        'message' => 'New note 2'
                    ],
                    'relationships' => [
                        'comments' => [
                            'data' => [
                                ['type' => 'comments', 'id' => '<toString(@comment2->id)>']
                            ]
                        ]
                    ]
                ]
            ]
        );

        self::assertEquals([$comment3Id], $this->getCommentIds($noteId));
    }
}
