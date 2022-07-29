<?php

namespace Oro\Bundle\TagBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\TagBundle\Entity\Taxonomy;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class TaxonomyTest extends RestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            '@OroTagBundle/Tests/Functional/Api/DataFixtures/taxonomy.yml'
        ]);
    }

    public function testGetList(): void
    {
        $response = $this->cget(['entity' => 'taxonomies']);
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'          => 'taxonomies',
                        'id'            => '<toString(@taxonomy1->id)>',
                        'attributes'    => [
                            'name'            => 'taxonomy1',
                            'backgroundColor' => '#FF0000',
                            'createdAt'       => '@taxonomy1->created->format("Y-m-d\TH:i:s\Z")',
                            'updatedAt'       => '@taxonomy1->updated->format("Y-m-d\TH:i:s\Z")'
                        ],
                        'relationships' => [
                            'owner'        => [
                                'data' => ['type' => 'users', 'id' => '<toString(@user->id)>']
                            ],
                            'organization' => [
                                'data' => ['type' => 'organizations', 'id' => '<toString(@organization->id)>']
                            ]
                        ]
                    ],
                    [
                        'type'          => 'taxonomies',
                        'id'            => '<toString(@taxonomy2->id)>',
                        'attributes'    => [
                            'name'            => 'taxonomy2',
                            'backgroundColor' => null,
                            'createdAt'       => '@taxonomy2->created->format("Y-m-d\TH:i:s\Z")',
                            'updatedAt'       => '@taxonomy2->updated->format("Y-m-d\TH:i:s\Z")'
                        ],
                        'relationships' => [
                            'owner'        => [
                                'data' => ['type' => 'users', 'id' => '<toString(@user->id)>']
                            ],
                            'organization' => [
                                'data' => ['type' => 'organizations', 'id' => '<toString(@organization->id)>']
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
        $response = $this->get(
            ['entity' => 'taxonomies', 'id' => '<toString(@taxonomy1->id)>']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => 'taxonomies',
                    'id'            => '<toString(@taxonomy1->id)>',
                    'attributes'    => [
                        'name'            => 'taxonomy1',
                        'backgroundColor' => '#FF0000',
                        'createdAt'       => '@taxonomy1->created->format("Y-m-d\TH:i:s\Z")',
                        'updatedAt'       => '@taxonomy1->updated->format("Y-m-d\TH:i:s\Z")'
                    ],
                    'relationships' => [
                        'owner'        => [
                            'data' => ['type' => 'users', 'id' => '<toString(@user->id)>']
                        ],
                        'organization' => [
                            'data' => ['type' => 'organizations', 'id' => '<toString(@organization->id)>']
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testDelete(): void
    {
        $taxonomyId = $this->getReference('taxonomy1')->getId();

        $this->delete(
            ['entity' => 'taxonomies', 'id' => (string)$taxonomyId]
        );

        $taxonomy = $this->getEntityManager()->find(Taxonomy::class, $taxonomyId);
        self::assertTrue(null === $taxonomy);
    }

    public function testDeleteList(): void
    {
        $taxonomyId = $this->getReference('taxonomy1')->getId();

        $this->cdelete(
            ['entity' => 'taxonomies'],
            ['filter[id]' => (string)$taxonomyId]
        );

        $taxonomy = $this->getEntityManager()->find(Taxonomy::class, $taxonomyId);
        self::assertTrue(null === $taxonomy);
    }

    public function testCreate(): void
    {
        $userId = $this->getReference('user')->getId();
        $organizationId = $this->getReference('organization')->getId();

        $data = [
            'data' => [
                'type'       => 'taxonomies',
                'attributes' => [
                    'name'            => 'new_taxonomy',
                    'backgroundColor' => '#0000FF'
                ]
            ]
        ];
        $response = $this->post(
            ['entity' => 'taxonomies'],
            $data
        );

        $expectedData = $data;
        $expectedData['data']['relationships']['owner']['data'] = [
            'type' => 'users',
            'id'   => (string)$userId
        ];
        $expectedData['data']['relationships']['organization']['data'] = [
            'type' => 'organizations',
            'id'   => (string)$organizationId
        ];
        $this->assertResponseContains($expectedData, $response);

        $taxonomy = $this->getEntityManager()->find(Taxonomy::class, $this->getResourceId($response));
        self::assertNotNull($taxonomy);
        self::assertEquals('new_taxonomy', $taxonomy->getName());
        self::assertEquals('#0000FF', $taxonomy->getBackgroundColor());
        self::assertEquals($userId, $taxonomy->getOwner()->getId());
        self::assertEquals($organizationId, $taxonomy->getOrganization()->getId());
    }

    public function testUpdate(): void
    {
        $taxonomyId = $this->getReference('taxonomy1')->getId();

        $response = $this->patch(
            ['entity' => 'taxonomies', 'id' => (string)$taxonomyId],
            [
                'data' => [
                    'type'       => 'taxonomies',
                    'id'         => (string)$taxonomyId,
                    'attributes' => [
                        'backgroundColor' => '#00ff00'
                    ]
                ]
            ]
        );

        $taxonomy = $this->getEntityManager()->find(Taxonomy::class, $this->getResourceId($response));
        self::assertNotNull($taxonomy);
        self::assertEquals('#00ff00', $taxonomy->getBackgroundColor());
    }

    public function testUpdateBackgroundColorToNull(): void
    {
        $taxonomyId = $this->getReference('taxonomy1')->getId();

        $response = $this->patch(
            ['entity' => 'taxonomies', 'id' => (string)$taxonomyId],
            [
                'data' => [
                    'type'       => 'taxonomies',
                    'id'         => (string)$taxonomyId,
                    'attributes' => [
                        'backgroundColor' => null
                    ]
                ]
            ]
        );

        $taxonomy = $this->getEntityManager()->find(Taxonomy::class, $this->getResourceId($response));
        self::assertNotNull($taxonomy);
        self::assertNull($taxonomy->getBackgroundColor());
    }

    public function testTryToCreateWithoutName(): void
    {
        $response = $this->post(
            ['entity' => 'taxonomies'],
            ['data' => ['type' => 'taxonomies']],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/attributes/name']
            ],
            $response
        );
    }

    public function testTryToSetNameToNull(): void
    {
        $taxonomyId = $this->getReference('taxonomy1')->getId();

        $response = $this->patch(
            ['entity' => 'taxonomies', 'id' => (string)$taxonomyId],
            [
                'data' => [
                    'type'       => 'taxonomies',
                    'id'         => (string)$taxonomyId,
                    'attributes' => [
                        'name' => null
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
                'source' => ['pointer' => '/data/attributes/name']
            ],
            $response
        );
    }

    public function testTryToSetBackgroundColorToEmptyString(): void
    {
        $taxonomyId = $this->getReference('taxonomy1')->getId();

        $response = $this->patch(
            ['entity' => 'taxonomies', 'id' => (string)$taxonomyId],
            [
                'data' => [
                    'type'       => 'taxonomies',
                    'id'         => (string)$taxonomyId,
                    'attributes' => [
                        'backgroundColor' => ''
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
                'source' => ['pointer' => '/data/attributes/backgroundColor']
            ],
            $response
        );
    }

    public function testTryToSetInvalidBackgroundColor(): void
    {
        $taxonomyId = $this->getReference('taxonomy1')->getId();

        $response = $this->patch(
            ['entity' => 'taxonomies', 'id' => (string)$taxonomyId],
            [
                'data' => [
                    'type'       => 'taxonomies',
                    'id'         => (string)$taxonomyId,
                    'attributes' => [
                        'backgroundColor' => '00FF00'
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'css color constraint',
                'detail' => 'This value is not a valid CSS color.',
                'source' => ['pointer' => '/data/attributes/backgroundColor']
            ],
            $response
        );
    }
}
