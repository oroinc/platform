<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiCustomization;

use Oro\Bundle\ApiBundle\Tests\Functional\DataFixtures\LoadEnumsData;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class EnumEntityTest extends RestJsonApiTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            LoadEnumsData::class,
            '@OroApiBundle/Tests/Functional/DataFixtures/custom_entities.yml'
        ]);
    }

    public function testGetList(): void
    {
        $response = $this->cget(['entity' => 'testapienum1']);
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'testapienum1',
                        'id' => '<toString(@enum1_0->internalId)>',
                        'attributes' => [
                            'name' => 'Item 0',
                            'priority' => -1,
                            'default' => true
                        ]
                    ],
                    [
                        'type' => 'testapienum1',
                        'id' => '<toString(@enum1_1->internalId)>',
                        'attributes' => [
                            'name' => 'Item 1',
                            'priority' => 0,
                            'default' => false
                        ]
                    ],
                    [
                        'type' => 'testapienum1',
                        'id' => '<toString(@enum1_2->internalId)>',
                        'attributes' => [
                            'name' => 'Item 2',
                            'priority' => 1,
                            'default' => false
                        ]
                    ],
                    [
                        'type' => 'testapienum1',
                        'id' => '<toString(@enum1_3->internalId)>',
                        'attributes' => [
                            'name' => 'Item 3',
                            'priority' => 2,
                            'default' => false
                        ]
                    ],
                    [
                        'type' => 'testapienum1',
                        'id' => '<toString(@enum1_4->internalId)>',
                        'attributes' => [
                            'name' => 'Item 4',
                            'priority' => 3,
                            'default' => false
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetListFilteredAndSortedByPriority(): void
    {
        $response = $this->cget(
            ['entity' => 'testapienum1'],
            ['filter[priority]' => '1..3', 'sort' => '-priority']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'testapienum1',
                        'id' => '<toString(@enum1_4->internalId)>',
                        'attributes' => [
                            'name' => 'Item 4',
                            'priority' => 3,
                            'default' => false
                        ]
                    ],
                    [
                        'type' => 'testapienum1',
                        'id' => '<toString(@enum1_3->internalId)>',
                        'attributes' => [
                            'name' => 'Item 3',
                            'priority' => 2,
                            'default' => false
                        ]
                    ],
                    [
                        'type' => 'testapienum1',
                        'id' => '<toString(@enum1_2->internalId)>',
                        'attributes' => [
                            'name' => 'Item 2',
                            'priority' => 1,
                            'default' => false
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGet(): void
    {
        $response = $this->get(['entity' => 'testapienum1', 'id' => '<toString(@enum1_1->internalId)>']);
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'testapienum1',
                    'id' => '<toString(@enum1_1->internalId)>',
                    'attributes' => [
                        'name' => 'Item 1',
                        'priority' => 0,
                        'default' => false
                    ]
                ]
            ],
            $response
        );
    }

    public function testTryToGetListWithTitles(): void
    {
        $response = $this->cget(['entity' => 'testapienum1'], ['meta' => 'title'], [], false);
        $this->assertResponseValidationError(
            [
                'title' => 'filter constraint',
                'detail' => 'The filter is not supported.',
                'source' => ['parameter' => 'meta']
            ],
            $response
        );
    }

    public function testTryToCreate(): void
    {
        $response = $this->post(
            ['entity' => 'testapienum1'],
            ['data' => ['type' => 'testapienum1', 'id' => 'new_item']],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testCreateForEditable(): void
    {
        $response = $this->post(
            ['entity' => 'testapienum2'],
            [
                'data' => [
                    'type' => 'testapienum2',
                    'id' => 'new_item',
                    'attributes' => [
                        'name' => 'New Item',
                        'priority' => 1,
                        'default' => true
                    ]
                ]
            ]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'testapienum2',
                    'id' => 'new_item',
                    'attributes' => [
                        'name' => 'New Item',
                        'priority' => 1,
                        'default' => true
                    ]
                ]
            ],
            $response
        );

        $entity = $this->getEntityManager()->find(EnumOption::class, 'api_enum2.new_item');
        self::assertEquals('api_enum2', $entity->getEnumCode());
        self::assertEquals('new_item', $entity->getInternalId());
        self::assertEquals('New Item', $entity->getName());
        self::assertEquals(1, $entity->getPriority());
        self::assertTrue($entity->isDefault());
    }

    public function testCreateForEditableWhenIncluded(): void
    {
        $response = $this->post(
            ['entity' => 'testapientity1'],
            [
                'data' => [
                    'type' => 'testapientity1',
                    'relationships' => [
                        'multiEnumField' => [
                            'data' => [
                                ['type' => 'testapienum2', 'id' => 'new_item']
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'testapienum2',
                        'id' => 'new_item',
                        'attributes' => [
                            'name' => 'New Item'
                        ]
                    ]
                ]
            ]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'testapientity1',
                    'relationships' => [
                        'multiEnumField' => [
                            'data' => [
                                ['type' => 'testapienum2', 'id' => 'new_item']
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'testapienum2',
                        'id' => 'new_item',
                        'attributes' => [
                            'name' => 'New Item',
                            'priority' => 0,
                            'default' => false
                        ]
                    ]
                ]
            ],
            $response
        );

        $entity = $this->getEntityManager()->find(EnumOption::class, 'api_enum2.new_item');
        self::assertEquals('api_enum2', $entity->getEnumCode());
        self::assertEquals('new_item', $entity->getInternalId());
        self::assertEquals('New Item', $entity->getName());
        self::assertEquals(0, $entity->getPriority());
        self::assertFalse($entity->isDefault());
    }

    public function testTryToCreateForEditableWhenIdIsNotProvided(): void
    {
        $response = $this->post(
            ['entity' => 'testapienum2'],
            [
                'data' => [
                    'type' => 'testapienum2',
                    'attributes' => [
                        'name' => 'New Item'
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'entity identifier constraint',
                'detail' => 'The identifier is mandatory',
                'source' => ['pointer' => '/data/id']
            ],
            $response
        );
    }

    public function testTryToCreateForEditableWhenIdIsEmpty(): void
    {
        $response = $this->post(
            ['entity' => 'testapienum2'],
            [
                'data' => [
                    'type' => 'testapienum2',
                    'id' => '',
                    'attributes' => [
                        'name' => 'New Item'
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'request data constraint',
                'detail' => 'The \'id\' property should not be blank',
                'source' => ['pointer' => '/data/id']
            ],
            $response
        );
    }

    public function testTryToCreateForEditableWhenIdAlreadyExists(): void
    {
        $response = $this->post(
            ['entity' => 'testapienum2'],
            [
                'data' => [
                    'type' => 'testapienum2',
                    'id' => '1',
                    'attributes' => [
                        'name' => 'Item 1'
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'conflict constraint',
                'detail' => 'The entity already exists.'
            ],
            $response,
            Response::HTTP_CONFLICT
        );
    }

    public function testTryToCreateForEditableWhenNameIsNotProvided(): void
    {
        $response = $this->post(
            ['entity' => 'testapienum2'],
            [
                'data' => [
                    'type' => 'testapienum2',
                    'id' => 'new_item'
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/attributes/name']
            ],
            $response
        );
    }

    public function testTryToCreateForEditableWhenNameIsEmpty(): void
    {
        $response = $this->post(
            ['entity' => 'testapienum2'],
            [
                'data' => [
                    'type' => 'testapienum2',
                    'id' => 'new_item',
                    'attributes' => [
                        'name' => ''
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/attributes/name']
            ],
            $response
        );
    }

    public function testTryToUpdate(): void
    {
        $response = $this->patch(
            ['entity' => 'testapienum1', 'id' => 'new_item'],
            ['data' => ['type' => 'testapienum1', 'id' => 'new_item']],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testUpdateForEditable(): void
    {
        $entityId = $this->getReference('enum2_1')->getId();
        $response = $this->patch(
            ['entity' => 'testapienum2', 'id' => '<toString(@enum2_1->internalId)>'],
            [
                'data' => [
                    'type' => 'testapienum2',
                    'id' => '<toString(@enum2_1->internalId)>',
                    'attributes' => [
                        'name' => 'Updated Item'
                    ]
                ]
            ]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'testapienum2',
                    'id' => '<toString(@enum2_1->internalId)>',
                    'attributes' => [
                        'name' => 'Updated Item',
                        'priority' => 0,
                        'default' => false
                    ]
                ]
            ],
            $response
        );

        $entity = $this->getEntityManager()->find(EnumOption::class, $entityId);
        self::assertEquals('Updated Item', $entity->getName());
    }

    public function testTryToDelete(): void
    {
        $response = $this->delete(
            ['entity' => 'testapienum1', 'id' => '<toString(@enum1_1->internalId)>'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testDeleteForEditable(): void
    {
        $entityId = $this->getReference('enum2_1')->getId();
        $this->delete(
            ['entity' => 'testapienum2', 'id' => '<toString(@enum2_1->internalId)>']
        );
        $entity = $this->getEntityManager()->find(EnumOption::class, $entityId);
        self::assertTrue(null === $entity);
    }

    public function testTryToDeleteList(): void
    {
        $response = $this->cdelete(
            ['entity' => 'testapienum1'],
            ['filter[id]' => '<toString(@enum1_1->internalId)>'],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testDeleteListForEditable(): void
    {
        $entityId = $this->getReference('enum2_1')->getId();
        $this->cdelete(
            ['entity' => 'testapienum2'],
            ['filter[id]' => '<toString(@enum2_1->internalId)>']
        );
        $entity = $this->getEntityManager()->find(EnumOption::class, $entityId);
        self::assertTrue(null === $entity);
    }

    public function testGetOptionsForList(): void
    {
        $response = $this->options(
            $this->getListRouteName(),
            ['entity' => 'testapienum1']
        );
        self::assertAllowResponseHeader($response, 'OPTIONS, GET');
    }

    public function testOptionsForItem(): void
    {
        $response = $this->options(
            $this->getItemRouteName(),
            ['entity' => 'testapienum1', 'id' => '<toString(@enum1_1->internalId)>']
        );
        self::assertAllowResponseHeader($response, 'OPTIONS, GET');
    }
}
