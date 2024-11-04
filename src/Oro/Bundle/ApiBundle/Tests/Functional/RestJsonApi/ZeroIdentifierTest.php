<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApi;

use Extend\Entity\TestApiE1;
use Oro\Bundle\ApiBundle\Tests\Functional\DataFixtures\LoadEnumsData;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

class ZeroIdentifierTest extends RestJsonApiTestCase
{
    private const TEST_ENUM_1_CLASS = 'Extend\Entity\EV_Api_Enum1';
    private const TEST_ENUM_2_CLASS = 'Extend\Entity\EV_Api_Enum2';

    #[\Override]
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
        $entityType = $this->getEntityType(self::TEST_ENUM_1_CLASS);
        $response = $this->get(['entity' => $entityType, 'id' => '<toString(@enum1_0->internalId)>']);

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => $entityType,
                    'id'   => '<toString(@enum1_0->internalId)>',
                ]
            ],
            $response
        );
    }

    public function testGetNotExistedResourceByZeroIdentifier()
    {
        $entityType = $this->getEntityType(self::TEST_ENUM_2_CLASS);
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
        $relatedEntityType = $this->getEntityType(self::TEST_ENUM_1_CLASS);

        $data = [
            'data' => [
                'type'          => $entityType,
                'relationships' => [
                    'enumField' => [
                        'data' => [
                            'type' => $relatedEntityType,
                            'id'   => $relatedEntity->getInternalId()
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
                                'id'   => $relatedEntity->getInternalId()
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
        self::assertSame($relatedEntity->getInternalId(), $entity->getEnumField()->getInternalId());
    }

    public function testUpdateRelationshipWithZeroIdentifier()
    {
        $entity = $this->getReference('entity_2');
        $relatedEntity = $this->getReference('enum1_0');
        $entityType = $this->getEntityType(TestApiE1::class);
        $relatedEntityType = $this->getEntityType(self::TEST_ENUM_1_CLASS);

        $data = [
            'data' => [
                'type'          => $entityType,
                'id'            => (string)$entity->getId(),
                'relationships' => [
                    'enumField' => [
                        'data' => [
                            'type' => $relatedEntityType,
                            'id'   => $relatedEntity->getInternalId()
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
                'id'   => $relatedEntity->getInternalId()
            ],
            $responseContent['data']['relationships']['enumField']['data']
        );

        // test that the data was updated
        $this->getEntityManager()->clear();
        $entity = $this->getEntityManager()->find(TestApiE1::class, $entity->getId());
        self::assertSame($relatedEntity->getInternalId(), $entity->getEnumField()->getInternalId());
    }

    public function testGetSubresourceWithZeroIdentifier()
    {
        $entity = $this->getReference('entity_1');
        $relatedEntity = $this->getReference('enum1_0');
        $entityType = $this->getEntityType(TestApiE1::class);
        $relatedEntityType = $this->getEntityType(self::TEST_ENUM_1_CLASS);

        $response = $this->getSubresource([
            'entity'      => $entityType,
            'id'          => $entity->getId(),
            'association' => 'enumField'
        ]);

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => $relatedEntityType,
                    'id'   => $relatedEntity->getInternalId(),
                ]
            ],
            $response
        );
        $responseContent = self::jsonToArray($response->getContent());
        self::assertArrayNotHasKey('meta', $responseContent['data']);
    }
}
