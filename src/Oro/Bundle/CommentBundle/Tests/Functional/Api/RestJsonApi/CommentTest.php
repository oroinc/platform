<?php

namespace Oro\Bundle\CommentBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\CommentBundle\Entity\Comment;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CommentTest extends RestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            '@OroCommentBundle/Tests/Functional/Api/DataFixtures/comment_data.yml'
        ]);
    }

    private function getTargetId(Comment $comment): ?int
    {
        $target = $comment->getTarget();
        if (null === $target) {
            return null;
        }

        return $target->getId();
    }

    public function testGetList(): void
    {
        $response = $this->cget(['entity' => 'comments']);
        $this->assertResponseContains('cget_comment.yml', $response);
    }

    public function testGetListWithTargetInIncludeFilter(): void
    {
        $response = $this->cget(
            ['entity' => 'comments'],
            ['include' => 'target,target.organization']
        );
        $this->assertResponseContains(
            [
                'data'     => [
                    [
                        'type'          => 'comments',
                        'id'            => '<toString(@comment1->id)>',
                        'relationships' => [
                            'target' => [
                                'data' => ['type' => 'notes', 'id' => '<toString(@note1->id)>']
                            ]
                        ]
                    ],
                    [
                        'type'          => 'comments',
                        'id'            => '<toString(@comment2->id)>',
                        'relationships' => [
                            'target' => [
                                'data' => ['type' => 'notes', 'id' => '<toString(@note1->id)>']
                            ]
                        ]
                    ],
                    [
                        'type'          => 'comments',
                        'id'            => '<toString(@comment3->id)>',
                        'relationships' => [
                            'target' => [
                                'data' => ['type' => 'notes', 'id' => '<toString(@note2->id)>']
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
            $response
        );
        $responseContent = self::jsonToArray($response->getContent());
        self::assertCount(3, $responseContent['included'], 'included');
    }

    public function testGet(): void
    {
        $response = $this->get(
            ['entity' => 'comments', 'id' => '<toString(@comment1->id)>']
        );
        $this->assertResponseContains('get_comment.yml', $response);
    }

    public function testCreate(): void
    {
        $note1Id = $this->getReference('note1')->getId();
        $response = $this->post(
            ['entity' => 'comments'],
            [
                'data' => [
                    'type'          => 'comments',
                    'attributes'    => [
                        'message' => 'Message for test comment'
                    ],
                    'relationships' => [
                        'target' => [
                            'data' => ['type' => 'notes', 'id' => '<toString(@note1->id)>']
                        ]
                    ]
                ]
            ]
        );

        $commentId = (int)$this->getResourceId($response);
        /** @var Comment $comment */
        $comment = $this->getEntityManager()->find(Comment::class, $commentId);
        self::assertEquals('Message for test comment', $comment->getMessage());
        self::assertEquals($note1Id, $this->getTargetId($comment));
    }

    public function testUpdate(): void
    {
        $commentId = $this->getReference('comment1')->getId();
        $note31Id = $this->getReference('note3')->getId();
        $data = [
            'data' => [
                'type'          => 'comments',
                'id'            => (string)$commentId,
                'attributes'    => [
                    'message' => 'New message for test comment'
                ],
                'relationships' => [
                    'target' => [
                        'data' => ['type' => 'notes', 'id' => '<toString(@note3->id)>']
                    ]
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => 'comments', 'id' => (string)$commentId],
            $data
        );
        $this->assertResponseContains($data, $response);

        /** @var Comment $comment */
        $comment = $this->getEntityManager()->find(Comment::class, $commentId);
        self::assertEquals('New message for test comment', $comment->getMessage());
        self::assertEquals($note31Id, $this->getTargetId($comment));
    }

    public function testDelete(): void
    {
        $commentId = $this->getReference('comment1')->getId();
        $this->delete(
            ['entity' => 'comments', 'id' => (string)$commentId]
        );
        self::assertTrue(null === $this->getEntityManager()->find(Comment::class, $commentId));
    }

    public function testGetSubresourceForTarget(): void
    {
        $response = $this->getSubresource(
            ['entity' => 'comments', 'id' => '<toString(@comment1->id)>', 'association' => 'target']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'notes',
                    'id'         => '<toString(@note1->id)>',
                    'attributes' => [
                        'message' => '<toString(@note1->message)>'
                    ]
                ]
            ],
            $response,
            true
        );
    }

    public function testGetSubresourceForTargetWithIncludeFilter(): void
    {
        $response = $this->getSubresource(
            ['entity' => 'comments', 'id' => '<toString(@comment1->id)>', 'association' => 'target'],
            ['include' => 'organization']
        );
        $this->assertResponseContains(
            [
                'data'     => [
                    'type'       => 'notes',
                    'id'         => '<toString(@note1->id)>',
                    'attributes' => [
                        'message' => '<toString(@note1->message)>'
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
        $responseContent = self::jsonToArray($response->getContent());
        self::assertCount(1, $responseContent['included']);
    }

    public function testGetRelationshipForTarget(): void
    {
        $response = $this->getRelationship(
            ['entity' => 'comments', 'id' => '<toString(@comment1->id)>', 'association' => 'target']
        );
        $this->assertResponseContains(
            ['data' => ['type' => 'notes', 'id' => '<toString(@note1->id)>']],
            $response,
            true
        );
        $responseContent = self::jsonToArray($response->getContent());
        self::assertArrayNotHasKey('attributes', $responseContent['data']);
        self::assertArrayNotHasKey('relationships', $responseContent['data']);
    }

    public function testUpdateRelationshipForTarget(): void
    {
        $commentId = $this->getReference('comment1')->getId();
        $note2Id = $this->getReference('note2')->getId();
        $this->patchRelationship(
            ['entity' => 'comments', 'id' => (string)$commentId, 'association' => 'target'],
            ['data' => ['type' => 'notes', 'id' => (string)$note2Id]]
        );
        /** @var Comment $comment */
        $comment = $this->getEntityManager()->find(Comment::class, $commentId);
        self::assertEquals($note2Id, $this->getTargetId($comment));
    }

    public function testTryToCreateWithoutTarget(): void
    {
        $response = $this->post(
            ['entity' => 'comments'],
            [
                'data' => [
                    'type'       => 'comments',
                    'attributes' => [
                        'message' => 'Message for test comment'
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
            ['entity' => 'comments'],
            [
                'data' => [
                    'type'          => 'comments',
                    'attributes'    => [
                        'message' => 'Message for test comment'
                    ],
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

    public function testTryToSetNullTarget(): void
    {
        $response = $this->patch(
            ['entity' => 'comments', 'id' => '<toString(@comment1->id)>'],
            [
                'data' => [
                    'type'          => 'comments',
                    'id'            => '<toString(@comment1->id)>',
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

    public function testTryToUpdateTargetAsRelationshipWithNullValue()
    {
        $response = $this->patchRelationship(
            ['entity' => 'comments', 'id' => '<toString(@comment1->id)>', 'association' => 'target'],
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
