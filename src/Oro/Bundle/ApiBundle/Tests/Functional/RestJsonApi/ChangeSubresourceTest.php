<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApi;

use Extend\Entity\TestApiE1 as Entity;
use Extend\Entity\TestApiE2 as TargetEntity;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 */
class ChangeSubresourceTest extends RestJsonApiTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->loadFixtures([
            '@OroApiBundle/Tests/Functional/DataFixtures/change_subresource.yml'
        ]);
    }

    public function testUpdateToOneSubresourceWithInvalidRequestData()
    {
        $entityType = $this->getEntityType(Entity::class);

        $response = $this->patchSubresource(
            ['entity' => $entityType, 'id' => '@entity->id', 'association' => 'biM2O'],
            ['data' => []],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'request data constraint',
                'detail' => 'The primary data object should not be empty',
                'source' => ['pointer' => '/data']
            ],
            $response
        );
    }

    public function testAddToOneSubresourceWithInvalidRequestData()
    {
        $entityType = $this->getEntityType(Entity::class);

        $response = $this->postSubresource(
            ['entity' => $entityType, 'id' => '@entity->id', 'association' => 'biM2O'],
            ['data' => []],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'request data constraint',
                'detail' => 'The primary data object should not be empty',
                'source' => ['pointer' => '/data']
            ],
            $response
        );
    }

    public function testDeleteToOneSubresourceWithInvalidRequestData()
    {
        $entityType = $this->getEntityType(Entity::class);

        $response = $this->deleteSubresource(
            ['entity' => $entityType, 'id' => '@entity->id', 'association' => 'biM2O'],
            ['data' => []],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'request data constraint',
                'detail' => 'The primary data object should not be empty',
                'source' => ['pointer' => '/data']
            ],
            $response
        );
    }

    public function testUpdateToManySubresourceWithInvalidRequestData()
    {
        $entityType = $this->getEntityType(Entity::class);

        $response = $this->patchSubresource(
            ['entity' => $entityType, 'id' => '@entity->id', 'association' => 'biM2M'],
            ['data' => []],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'request data constraint',
                'detail' => 'The primary data object collection should not be empty',
                'source' => ['pointer' => '/data']
            ],
            $response
        );
    }

    public function testAddToManySubresourceWithInvalidRequestData()
    {
        $entityType = $this->getEntityType(Entity::class);

        $response = $this->postSubresource(
            ['entity' => $entityType, 'id' => '@entity->id', 'association' => 'biM2M'],
            ['data' => []],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'request data constraint',
                'detail' => 'The primary data object collection should not be empty',
                'source' => ['pointer' => '/data']
            ],
            $response
        );
    }

    public function testDeleteToManySubresourceWithInvalidRequestData()
    {
        $entityType = $this->getEntityType(Entity::class);

        $response = $this->deleteSubresource(
            ['entity' => $entityType, 'id' => '@entity->id', 'association' => 'biM2M'],
            ['data' => []],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'request data constraint',
                'detail' => 'The primary data object collection should not be empty',
                'source' => ['pointer' => '/data']
            ],
            $response
        );
    }

    public function testUpdateCustomSubresourceWithInvalidRequestData()
    {
        $entityType = $this->getEntityType(Entity::class);

        $response = $this->patchSubresource(
            ['entity' => $entityType, 'id' => '@entity->id', 'association' => 'custom'],
            ['data' => []],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'request data constraint',
                'detail' => 'The primary data object should not be empty',
                'source' => ['pointer' => '/data']
            ],
            $response
        );
    }

    public function testAddCustomSubresourceWithInvalidRequestData()
    {
        $entityType = $this->getEntityType(Entity::class);

        $response = $this->postSubresource(
            ['entity' => $entityType, 'id' => '@entity->id', 'association' => 'custom'],
            ['data' => []],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'request data constraint',
                'detail' => 'The primary data object should not be empty',
                'source' => ['pointer' => '/data']
            ],
            $response
        );
    }

    public function testDeleteCustomSubresourceWithInvalidRequestData()
    {
        $entityType = $this->getEntityType(Entity::class);

        $response = $this->deleteSubresource(
            ['entity' => $entityType, 'id' => '@entity->id', 'association' => 'custom'],
            ['data' => []],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'request data constraint',
                'detail' => 'The primary data object should not be empty',
                'source' => ['pointer' => '/data']
            ],
            $response
        );
    }

    public function testUpdateToOneSubresource()
    {
        $entityType = $this->getEntityType(Entity::class);
        $entityId = $this->getReference('entity')->getId();
        $targetEntityType = $this->getEntityType(TargetEntity::class);
        $targetEntityId = $this->getReference('target1')->getId();

        $data = [
            'data' => [
                'type'          => $targetEntityType,
                'id'            => (string)$targetEntityId,
                'attributes'    => [
                    'name' => 'Updated Target Entity 1'
                ],
                'relationships' => [
                    'biO2MOwner' => [
                        'data' => ['type' => $entityType, 'id' => (string)$entityId]
                    ]
                ]
            ]
        ];

        $response = $this->patchSubresource(
            ['entity' => $entityType, 'id' => (string)$entityId, 'association' => 'biM2O'],
            $data
        );

        $this->assertResponseContains($data, $response);

        $updatedTargetEntity = $this->getEntityManager()
            ->getRepository(TargetEntity::class)
            ->find($targetEntityId);
        self::assertNotNull($updatedTargetEntity);
        self::assertSame('Updated Target Entity 1', $updatedTargetEntity->getName());
        self::assertSame($entityId, $updatedTargetEntity->getBiO2MOwner()->getId());
    }

    public function testUpdateToOneSubresourceWithInvalidData()
    {
        $entityType = $this->getEntityType(Entity::class);
        $targetEntityType = $this->getEntityType(TargetEntity::class);

        $response = $this->patchSubresource(
            ['entity' => $entityType, 'id' => '@entity->id', 'association' => 'biM2O'],
            [
                'data' => [
                    'type'          => $targetEntityType,
                    'id'            => '<toString(@target1->id)>',
                    'relationships' => [
                        'biO2MOwner' => [
                            'data' => ['type' => $entityType, 'id' => 'foo']
                        ]
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'entity identifier constraint',
                'detail' => 'Expected integer value. Given "foo".',
                'source' => ['pointer' => '/data/relationships/biO2MOwner/data/id']
            ],
            $response
        );
    }

    public function testUpdateToOneSubresourceWithNotExistingRelatedEntity()
    {
        $entityType = $this->getEntityType(Entity::class);
        $targetEntityType = $this->getEntityType(TargetEntity::class);

        $response = $this->patchSubresource(
            ['entity' => $entityType, 'id' => '@entity->id', 'association' => 'biM2O'],
            [
                'data' => [
                    'type'          => $targetEntityType,
                    'id'            => '<toString(@target1->id)>',
                    'relationships' => [
                        'biO2MOwner' => [
                            'data' => ['type' => $entityType, 'id' => '123456789']
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
                'detail' => 'This value is not valid.',
                'source' => ['pointer' => '/data/relationships/biO2MOwner/data']
            ],
            $response
        );
    }

    public function testAddToOneSubresource()
    {
        $entityType = $this->getEntityType(Entity::class);
        $targetEntityType = $this->getEntityType(TargetEntity::class);

        $response = $this->postSubresource(
            ['entity' => $entityType, 'id' => '@entity->id', 'association' => 'biM2O'],
            [
                'data' => [
                    'type'       => $targetEntityType,
                    'id'         => '<toString(@target1->id)>',
                    'attributes' => [
                        'name' => 'Updated Target Entity 1'
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'runtime exception',
                'detail' => 'The form builder was not created.'
            ],
            $response,
            Response::HTTP_INTERNAL_SERVER_ERROR
        );
    }

    public function testDeleteToOneSubresource()
    {
        $entityType = $this->getEntityType(Entity::class);
        $targetEntityType = $this->getEntityType(TargetEntity::class);

        $response = $this->deleteSubresource(
            ['entity' => $entityType, 'id' => '@entity->id', 'association' => 'biM2O'],
            [
                'data' => [
                    'type'       => $targetEntityType,
                    'id'         => '<toString(@target1->id)>',
                    'attributes' => [
                        'name' => 'Updated Target Entity 1'
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'runtime exception',
                'detail' => 'The form builder was not created.'
            ],
            $response,
            Response::HTTP_INTERNAL_SERVER_ERROR
        );
    }

    public function testUpdateToManySubresource()
    {
        $entityType = $this->getEntityType(Entity::class);
        $targetEntityType = $this->getEntityType(TargetEntity::class);

        $response = $this->patchSubresource(
            ['entity' => $entityType, 'id' => '@entity->id', 'association' => 'biM2M'],
            [
                'data' => [
                    [
                        'type'       => $targetEntityType,
                        'id'         => '<toString(@target1->id)>',
                        'attributes' => [
                            'name' => 'Updated Target Entity 1'
                        ]
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'runtime exception',
                'detail' => 'The form builder was not created.'
            ],
            $response,
            Response::HTTP_INTERNAL_SERVER_ERROR
        );
    }

    public function testUpdateToManySubresourceWithInvalidData()
    {
        $entityType = $this->getEntityType(Entity::class);
        $targetEntityType = $this->getEntityType(TargetEntity::class);

        $response = $this->patchSubresource(
            ['entity' => $entityType, 'id' => '@entity->id', 'association' => 'biM2M'],
            [
                'data' => [
                    [
                        'type'          => $targetEntityType,
                        'id'            => '<toString(@target1->id)>',
                        'relationships' => [
                            'biO2MOwner' => [
                                'data' => ['type' => $entityType, 'id' => 'foo']
                            ]
                        ]
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'entity identifier constraint',
                'detail' => 'Expected integer value. Given "foo".',
                'source' => ['pointer' => '/data/0/relationships/biO2MOwner/data/id']
            ],
            $response
        );
    }

    public function testAddToManySubresource()
    {
        $entityType = $this->getEntityType(Entity::class);
        $targetEntityType = $this->getEntityType(TargetEntity::class);

        $response = $this->postSubresource(
            ['entity' => $entityType, 'id' => '@entity->id', 'association' => 'biM2M'],
            [
                'data' => [
                    [
                        'type'       => $targetEntityType,
                        'id'         => '<toString(@target1->id)>',
                        'attributes' => [
                            'name' => 'Updated Target Entity 1'
                        ]
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'runtime exception',
                'detail' => 'The form builder was not created.'
            ],
            $response,
            Response::HTTP_INTERNAL_SERVER_ERROR
        );
    }

    public function testDeleteToManySubresource()
    {
        $entityType = $this->getEntityType(Entity::class);
        $targetEntityType = $this->getEntityType(TargetEntity::class);

        $response = $this->deleteSubresource(
            ['entity' => $entityType, 'id' => '@entity->id', 'association' => 'biM2M'],
            [
                'data' => [
                    [
                        'type'       => $targetEntityType,
                        'id'         => '<toString(@target1->id)>',
                        'attributes' => [
                            'name' => 'Updated Target Entity 1'
                        ]
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'runtime exception',
                'detail' => 'The form builder was not created.'
            ],
            $response,
            Response::HTTP_INTERNAL_SERVER_ERROR
        );
    }

    public function testUpdateCustomSubresource()
    {
        $entityType = $this->getEntityType(Entity::class);
        $targetEntityType = $this->getEntityType(TargetEntity::class);

        $response = $this->patchSubresource(
            ['entity' => $entityType, 'id' => '@entity->id', 'association' => 'custom'],
            [
                'data' => [
                    'type'       => $targetEntityType,
                    'id'         => '<toString(@target1->id)>',
                    'attributes' => [
                        'name' => 'Updated Target Entity 1'
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'runtime exception',
                'detail' => 'The form builder was not created.'
            ],
            $response,
            Response::HTTP_INTERNAL_SERVER_ERROR
        );
    }

    public function testAddCustomSubresource()
    {
        $entityType = $this->getEntityType(Entity::class);
        $targetEntityType = $this->getEntityType(TargetEntity::class);

        $response = $this->postSubresource(
            ['entity' => $entityType, 'id' => '@entity->id', 'association' => 'custom'],
            [
                'data' => [
                    'type'       => $targetEntityType,
                    'id'         => '<toString(@target1->id)>',
                    'attributes' => [
                        'name' => 'Updated Target Entity 1'
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'runtime exception',
                'detail' => 'The form builder was not created.'
            ],
            $response,
            Response::HTTP_INTERNAL_SERVER_ERROR
        );
    }

    public function testDeleteCustomSubresource()
    {
        $entityType = $this->getEntityType(Entity::class);
        $targetEntityType = $this->getEntityType(TargetEntity::class);

        $response = $this->deleteSubresource(
            ['entity' => $entityType, 'id' => '@entity->id', 'association' => 'custom'],
            [
                'data' => [
                    'type'       => $targetEntityType,
                    'id'         => '<toString(@target1->id)>',
                    'attributes' => [
                        'name' => 'Updated Target Entity 1'
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'runtime exception',
                'detail' => 'The form builder was not created.'
            ],
            $response,
            Response::HTTP_INTERNAL_SERVER_ERROR
        );
    }
}
