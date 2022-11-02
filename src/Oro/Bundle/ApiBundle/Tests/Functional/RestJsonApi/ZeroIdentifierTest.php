<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApi;

use Extend\Entity\EV_Api_Enum1 as TestEnum1;
use Extend\Entity\EV_Api_Enum2 as TestEnum2;
use Extend\Entity\TestApiE1;
use Oro\Bundle\ApiBundle\Tests\Functional\DataFixtures\LoadEnumsData;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

class ZeroIdentifierTest extends RestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            LoadEnumsData::class,
            '@OroApiBundle/Tests/Functional/DataFixtures/zero_identifier.yml'
        ]);
    }

    public function testGetResourceByZeroIdentifier()
    {
        $entityType = $this->getEntityType(TestEnum1::class);
        $response = $this->get(['entity' => $entityType, 'id' => '<toString(@enum1_0->id)>']);

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => $entityType,
                    'id'   => '<toString(@enum1_0->id)>',
                ]
            ],
            $response
        );
    }

    public function testGetNotExistedResourceByZeroIdentifier()
    {
        $entityType = $this->getEntityType(TestEnum2::class);
        $response = $this->request(
            'GET',
            $this->getUrl($this->getItemRouteName(), ['entity' => $entityType, 'id' => '0'])
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testCreateWithRelationshipWithZeroIdentifier()
    {
        $entityType = $this->getEntityType(TestApiE1::class);
        $relatedEntity = $this->getReference('enum1_0');
        $relatedEntityType = $this->getEntityType(TestEnum1::class);

        $data = [
            'data' => [
                'type'          => $entityType,
                'relationships' => [
                    'enumField' => [
                        'data' => [
                            'type' => $relatedEntityType,
                            'id'   => $relatedEntity->getId()
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->post(['entity' => $entityType], $data);

        $this->assertResponseContains(
            [
                'data' => [
                    'relationships' => [
                        'enumField' => [
                            'data' => [
                                'type' => $relatedEntityType,
                                'id'   => $relatedEntity->getId()
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );

        // test that the data was created
        $this->getEntityManager()->clear();
        $entity = $this->getEntityManager()->find(TestApiE1::class, (int)$this->getResourceId($response));
        self::assertSame($relatedEntity->getId(), $entity->getEnumField()->getId());
    }

    public function testUpdateRelationshipWithZeroIdentifier()
    {
        $entity = $this->getReference('entity_2');
        $relatedEntity = $this->getReference('enum1_0');
        $entityType = $this->getEntityType(TestApiE1::class);
        $relatedEntityType = $this->getEntityType(TestEnum1::class);

        $data = [
            'data' => [
                'type'          => $entityType,
                'id'            => (string)$entity->getId(),
                'relationships' => [
                    'enumField' => [
                        'data' => [
                            'type' => $relatedEntityType,
                            'id'   => $relatedEntity->getId()
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->patch(
            ['entity' => $entityType, 'id' => $entity->getId()],
            $data
        );

        $responseContent = self::jsonToArray($response->getContent());
        self::assertEquals(
            [
                'type' => $relatedEntityType,
                'id'   => $relatedEntity->getId()
            ],
            $responseContent['data']['relationships']['enumField']['data']
        );

        // test that the data was updated
        $this->getEntityManager()->clear();
        $entity = $this->getEntityManager()->find(TestApiE1::class, $entity->getId());
        self::assertSame($relatedEntity->getId(), $entity->getEnumField()->getId());
    }

    public function testGetSubresourceWithZeroIdentifier()
    {
        $entity = $this->getReference('entity_1');
        $relatedEntity = $this->getReference('enum1_0');
        $entityType = $this->getEntityType(TestApiE1::class);
        $relatedEntityType = $this->getEntityType(TestEnum1::class);

        $response = $this->getSubresource([
            'entity'      => $entityType,
            'id'          => $entity->getId(),
            'association' => 'enumField'
        ]);

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => $relatedEntityType,
                    'id'   => $relatedEntity->getId(),
                ]
            ],
            $response
        );
        $responseContent = self::jsonToArray($response->getContent());
        self::assertArrayNotHasKey('meta', $responseContent['data']);
    }
}
