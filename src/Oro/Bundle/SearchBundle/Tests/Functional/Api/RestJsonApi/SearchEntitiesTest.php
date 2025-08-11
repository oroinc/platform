<?php

namespace Oro\Bundle\SearchBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\JsonApiDocContainsConstraint;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Test\Functional\RolePermissionExtension;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group search
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class SearchEntitiesTest extends RestJsonApiTestCase
{
    use RolePermissionExtension;

    protected function setUp(): void
    {
        parent::setUp();
        $this->updateRolePermission('ROLE_ADMINISTRATOR', BusinessUnit::class, AccessLevel::NONE_LEVEL);
    }

    private static function filterResponseContent(Response $response): array
    {
        $entityTypes = ['users', 'businessunits'];
        $responseContent = self::jsonToArray($response->getContent());
        $filteredResponseContent = ['data' => []];
        foreach ($responseContent['data'] as $item) {
            $entityType = $item['attributes']['entityType'];
            if (in_array($entityType, $entityTypes, true)) {
                $filteredResponseContent['data'][] = $item;
            }
        }
        usort($filteredResponseContent['data'], function (array $item1, array $item2): int {
            return strcmp($item1['id'], $item2['id']);
        });

        return $filteredResponseContent;
    }

    private static function assertResponseContent(array $expectedContent, array $content): void
    {
        self::assertThat($content, new JsonApiDocContainsConstraint($expectedContent, false, true));
    }

    public function testGetList(): void
    {
        $response = $this->cget(['entity' => 'searchentities']);
        self::assertResponseContent(
            [
                'data' => [
                    [
                        'type' => 'searchentities',
                        'id' => 'businessunits',
                        'attributes' => [
                            'entityType' => 'businessunits',
                            'entityName' => 'Business Unit',
                            'searchable' => false
                        ]
                    ],
                    [
                        'type' => 'searchentities',
                        'id' => 'users',
                        'attributes' => [
                            'entityType' => 'users',
                            'entityName' => 'User',
                            'searchable' => true
                        ]
                    ]
                ]
            ],
            self::filterResponseContent($response)
        );
        $responseData = self::jsonToArray($response->getContent());
        foreach ($responseData['data'] as $item) {
            self::assertArrayHasKey('fields', $item['attributes']);
        }
        self::assertArrayContains(
            [
                [
                    'name' => 'name',
                    'type' => 'text',
                    'entityFields' => ['name']
                ]
            ],
            $responseData['data'][0]['attributes']['fields']
        );
    }

    public function testGetListWithFieldset(): void
    {
        $response = $this->cget(
            ['entity' => 'searchentities'],
            ['fields[searchentities]' => 'entityType,entityName']
        );
        self::assertResponseContent(
            [
                'data' => [
                    [
                        'type' => 'searchentities',
                        'id' => 'businessunits',
                        'attributes' => [
                            'entityType' => 'businessunits',
                            'entityName' => 'Business Unit'
                        ]
                    ],
                    [
                        'type' => 'searchentities',
                        'id' => 'users',
                        'attributes' => [
                            'entityType' => 'users',
                            'entityName' => 'User'
                        ]
                    ]
                ]
            ],
            self::filterResponseContent($response)
        );
        $responseData = self::jsonToArray($response->getContent());
        foreach ($responseData['data'] as $item) {
            self::assertCount(2, $item['attributes']);
        }
    }

    public function testGetListFilteredByOneEntity(): void
    {
        $response = $this->cget(
            ['entity' => 'searchentities'],
            ['filter' => ['entityType' => 'businessunits']]
        );
        self::assertResponseContent(
            [
                'data' => [
                    [
                        'type' => 'searchentities',
                        'id' => 'businessunits',
                        'attributes' => [
                            'entityType' => 'businessunits'
                        ]
                    ]
                ]
            ],
            self::filterResponseContent($response)
        );
        $responseData = self::jsonToArray($response->getContent());
        self::assertCount(1, $responseData['data']);
    }

    public function testGetListFilteredBySeveralEntities(): void
    {
        $response = $this->cget(
            ['entity' => 'searchentities'],
            ['filter' => ['entityType' => 'businessunits,users']]
        );
        self::assertResponseContent(
            [
                'data' => [
                    [
                        'type' => 'searchentities',
                        'id' => 'businessunits',
                        'attributes' => [
                            'entityType' => 'businessunits'
                        ]
                    ],
                    [
                        'type' => 'searchentities',
                        'id' => 'users',
                        'attributes' => [
                            'entityType' => 'users'
                        ]
                    ]
                ]
            ],
            self::filterResponseContent($response)
        );
        $responseData = self::jsonToArray($response->getContent());
        self::assertCount(2, $responseData['data']);
    }

    public function testTryToGetListFilteredByUnknownEntity(): void
    {
        $response = $this->cget(
            ['entity' => 'searchentities'],
            ['filter' => ['entityType' => 'unknown']],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'filter constraint',
                'detail' => 'The plural alias "unknown" is not associated with any entity class.',
                'source' => ['parameter' => 'filter[entityType]']
            ],
            $response
        );
    }

    public function testGetListFilteredBySearchableTrue(): void
    {
        $response = $this->cget(
            ['entity' => 'searchentities'],
            ['filter' => ['searchable' => true]]
        );
        self::assertResponseContent(
            [
                'data' => [
                    [
                        'type' => 'searchentities',
                        'id' => 'users',
                        'attributes' => [
                            'entityType' => 'users'
                        ]
                    ]
                ]
            ],
            self::filterResponseContent($response)
        );
    }

    public function testGetListFilteredBySearchableFalse(): void
    {
        $response = $this->cget(
            ['entity' => 'searchentities'],
            ['filter' => ['searchable' => false]]
        );
        self::assertResponseContent(
            [
                'data' => [
                    [
                        'type' => 'searchentities',
                        'id' => 'businessunits',
                        'attributes' => [
                            'entityType' => 'businessunits'
                        ]
                    ]
                ]
            ],
            self::filterResponseContent($response)
        );
    }

    public function testGetListFilteredByEntitiesAndSearchable(): void
    {
        $response = $this->cget(
            ['entity' => 'searchentities'],
            ['filter' => ['entityType' => 'businessunits,users', 'searchable' => true]]
        );
        self::assertResponseContent(
            [
                'data' => [
                    [
                        'type' => 'searchentities',
                        'id' => 'users',
                        'attributes' => [
                            'entityType' => 'users'
                        ]
                    ]
                ]
            ],
            self::filterResponseContent($response)
        );
        $responseData = self::jsonToArray($response->getContent());
        self::assertCount(1, $responseData['data']);
    }

    public function testGet(): void
    {
        $response = $this->get(['entity' => 'searchentities', 'id' => 'users']);
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'searchentities',
                    'id' => 'users',
                    'attributes' => [
                        'entityType' => 'users',
                        'entityName' => 'User',
                        'searchable' => true
                    ]
                ]
            ],
            $response
        );
    }

    public function testTryToGetUnknownEntity(): void
    {
        $response = $this->get(['entity' => 'searchentities', 'id' => 'unknown'], [], [], false);
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToGetNotAccessibleEntity(): void
    {
        $response = $this->get(['entity' => 'searchentities', 'id' => 'testapiunaccessiblemodel'], [], [], false);
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToCreate(): void
    {
        $response = $this->post(
            ['entity' => 'searchentities'],
            ['data' => ['type' => 'searchentities']],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToUpdate(): void
    {
        $response = $this->patch(
            ['entity' => 'searchentities', 'id' => 'users'],
            ['data' => ['type' => 'searchentities', 'id' => 'users']],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDelete(): void
    {
        $response = $this->delete(
            ['entity' => 'searchentities', 'id' => 'users'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDeleteList(): void
    {
        $response = $this->cdelete(
            ['entity' => 'searchentities'],
            ['filter[id]' => 'users'],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }
}
