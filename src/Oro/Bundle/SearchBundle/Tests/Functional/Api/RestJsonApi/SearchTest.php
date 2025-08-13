<?php

namespace Oro\Bundle\SearchBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\JsonApiDocContainsConstraint;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SearchBundle\Tests\Functional\SearchExtensionTrait;
use Oro\Bundle\UserBundle\Entity\User;
use PHPUnit\Framework\ExpectationFailedException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Yaml\Yaml;

/**
 * @group search
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class SearchTest extends RestJsonApiTestCase
{
    use SearchExtensionTrait;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        // do the reindex because by some unknown reasons the search index is empty or missed
        // after upgrade from old application version
        self::reindex();
    }

    #[\Override]
    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();
    }

    private static function filterResponseContent(Response $response): array
    {
        $entityTypes = ['users', 'businessunits'];
        $responseContent = self::jsonToArray($response->getContent());
        $filteredResponseContent = ['data' => []];
        foreach ($responseContent['data'] as $item) {
            $entityType = $item['relationships']['entity']['data']['type'];
            if (in_array($entityType, $entityTypes, true)) {
                $filteredResponseContent['data'][] = $item;
            }
        }
        if (isset($responseContent['included'])) {
            $filteredResponseContent['included'] = [];
            foreach ($responseContent['included'] as $item) {
                $entityType = $item['type'];
                if (in_array($entityType, $entityTypes, true)) {
                    $filteredResponseContent['included'][] = $item;
                }
            }
        }
        if (isset($responseContent['meta'])) {
            $filteredResponseContent['meta'] = $responseContent['meta'];
        }

        return $filteredResponseContent;
    }

    private static function assertResponseContent(array $expectedContent, array $content): void
    {
        try {
            self::assertThat($content, new JsonApiDocContainsConstraint($expectedContent, false, false));
        } catch (ExpectationFailedException $e) {
            // add the response data to simplify finding an error when a test is failed
            throw new ExpectationFailedException($e->getMessage() . "\nResponse Data:\n" . Yaml::dump($content, 8));
        }
    }

    private function getEntityId(string $entityClass): int
    {
        return $this->getEntityManager($entityClass)
            ->getRepository($entityClass)
            ->createQueryBuilder('e')
            ->orderBy('e.id')
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleResult()
            ->getId();
    }

    public function testSearchWithoutSearchText(): void
    {
        $userId = $this->getEntityId(User::class);
        $businessUnitId = $this->getEntityId(BusinessUnit::class);
        $response = $this->cget(['entity' => 'search'], ['page[size]' => -1]);
        $filteredResponseContent = self::filterResponseContent($response);
        $expectedContent = [
            'data' => [
                [
                    'type' => 'search',
                    'id' => 'users-' . $userId,
                    'links' => [
                        'entityUrl' => $this->getUrl('oro_user_view', ['id' => $userId], true)
                    ],
                    'attributes' => [
                        'entityName' => 'John Doe'
                    ],
                    'relationships' => [
                        'entity' => ['data' => ['type' => 'users', 'id' => (string)$userId]]
                    ]
                ],
                [
                    'type' => 'search',
                    'id' => 'businessunits-' . $businessUnitId,
                    'links' => [
                        'entityUrl' => $this->getUrl('oro_business_unit_view', ['id' => $businessUnitId], true)
                    ],
                    'attributes' => [
                        'entityName' => 'Main'
                    ],
                    'relationships' => [
                        'entity' => ['data' => ['type' => 'businessunits', 'id' => (string)$businessUnitId]]
                    ]
                ]
            ]
        ];
        self::assertResponseContent($expectedContent, $filteredResponseContent);
    }

    public function testSearchBySpecifiedEntityTypes(): void
    {
        $businessUnitId = $this->getEntityId(BusinessUnit::class);
        $response = $this->cget(['entity' => 'search'], ['filter' => ['entities' => 'businessunits']]);
        $filteredResponseContent = self::filterResponseContent($response);
        $expectedContent = [
            'data' => [
                [
                    'type' => 'search',
                    'id' => 'businessunits-' . $businessUnitId,
                    'links' => [
                        'entityUrl' => $this->getUrl('oro_business_unit_view', ['id' => $businessUnitId], true)
                    ],
                    'attributes' => [
                        'entityName' => 'Main'
                    ],
                    'relationships' => [
                        'entity' => ['data' => ['type' => 'businessunits', 'id' => (string)$businessUnitId]]
                    ]
                ]
            ]
        ];
        self::assertResponseContent($expectedContent, $filteredResponseContent);
    }

    public function testSearchBySearchText(): void
    {
        $userId = $this->getEntityId(User::class);
        $response = $this->cget(['entity' => 'search'], ['filter' => ['searchText' => 'Doe']]);
        $filteredResponseContent = self::filterResponseContent($response);
        $expectedContent = [
            'data' => [
                [
                    'type' => 'search',
                    'id' => 'users-' . $userId,
                    'links' => [
                        'entityUrl' => $this->getUrl('oro_user_view', ['id' => $userId], true)
                    ],
                    'attributes' => [
                        'entityName' => 'John Doe'
                    ],
                    'relationships' => [
                        'entity' => ['data' => ['type' => 'users', 'id' => (string)$userId]]
                    ]
                ]
            ]
        ];
        self::assertResponseContent($expectedContent, $filteredResponseContent);
        self::assertArrayNotHasKey('meta', $filteredResponseContent);
    }

    public function testSearchWithInclude(): void
    {
        $userId = $this->getEntityId(User::class);
        $businessUnitId = $this->getEntityId(BusinessUnit::class);
        $response = $this->cget(
            ['entity' => 'search'],
            ['include' => 'entity', 'filter' => ['entities' => 'users,businessunits']]
        );
        $filteredResponseContent = self::filterResponseContent($response);
        $expectedContent = [
            'data' => [
                [
                    'type' => 'search',
                    'id' => 'users-' . $userId,
                    'links' => [
                        'entityUrl' => $this->getUrl('oro_user_view', ['id' => $userId], true)
                    ],
                    'attributes' => [
                        'entityName' => 'John Doe'
                    ],
                    'relationships' => [
                        'entity' => ['data' => ['type' => 'users', 'id' => (string)$userId]]
                    ]
                ],
                [
                    'type' => 'search',
                    'id' => 'businessunits-' . $businessUnitId,
                    'links' => [
                        'entityUrl' => $this->getUrl('oro_business_unit_view', ['id' => $businessUnitId], true)
                    ],
                    'attributes' => [
                        'entityName' => 'Main'
                    ],
                    'relationships' => [
                        'entity' => ['data' => ['type' => 'businessunits', 'id' => (string)$businessUnitId]]
                    ]
                ]
            ],
            'included' => [
                [
                    'type' => 'users',
                    'id' => (string)$userId,
                    'attributes' => [
                        'username' => 'admin'
                    ]
                ],
                [
                    'type' => 'businessunits',
                    'id' => (string)$businessUnitId,
                    'attributes' => [
                        'name' => 'Main'
                    ]
                ]
            ]
        ];
        self::assertResponseContent($expectedContent, $filteredResponseContent);
    }

    public function testSearchBySearchQuery(): void
    {
        $userId = $this->getEntityId(User::class);
        $response = $this->cget(['entity' => 'search'], ['filter' => ['searchQuery' => 'lastName ~ Doe']]);
        $filteredResponseContent = self::filterResponseContent($response);
        $expectedContent = [
            'data' => [
                [
                    'type' => 'search',
                    'id' => 'users-' . $userId,
                    'links' => [
                        'entityUrl' => $this->getUrl('oro_user_view', ['id' => $userId], true)
                    ],
                    'attributes' => [
                        'entityName' => 'John Doe'
                    ],
                    'relationships' => [
                        'entity' => ['data' => ['type' => 'users', 'id' => (string)$userId]]
                    ]
                ]
            ]
        ];
        self::assertResponseContent($expectedContent, $filteredResponseContent);
    }

    public function testSearchBySearchQueryAndSearchText(): void
    {
        $userId = $this->getEntityId(User::class);
        $response = $this->cget(
            ['entity' => 'search'],
            ['filter' => ['searchQuery' => 'lastName = Doe', 'searchText' => 'John']]
        );
        $filteredResponseContent = self::filterResponseContent($response);
        $expectedContent = [
            'data' => [
                [
                    'type' => 'search',
                    'id' => 'users-' . $userId,
                    'links' => [
                        'entityUrl' => $this->getUrl('oro_user_view', ['id' => $userId], true)
                    ],
                    'attributes' => [
                        'entityName' => 'John Doe'
                    ],
                    'relationships' => [
                        'entity' => ['data' => ['type' => 'users', 'id' => (string)$userId]]
                    ]
                ]
            ]
        ];
        self::assertResponseContent($expectedContent, $filteredResponseContent);
    }

    public function testSearchBySearchQueryAndSearchTextWithEmptyResult(): void
    {
        $response = $this->cget(
            ['entity' => 'search'],
            ['filter' => ['searchQuery' => 'lastName ~ NON_EXIST', 'searchText' => 'John']]
        );
        $filteredResponseContent = self::filterResponseContent($response);
        $expectedContent = ['data' => []];
        self::assertResponseContent($expectedContent, $filteredResponseContent);
    }

    public function testSearchBySearchTextAndAggregation(): void
    {
        $userId = $this->getEntityId(User::class);
        $response = $this->cget(
            ['entity' => 'search'],
            ['filter' => ['searchText' => 'Doe', 'aggregations' => 'lastName count']]
        );
        $filteredResponseContent = self::filterResponseContent($response);
        $expectedContent = [
            'data' => [
                [
                    'type' => 'search',
                    'id' => 'users-' . $userId,
                    'links' => [
                        'entityUrl' => $this->getUrl('oro_user_view', ['id' => $userId], true)
                    ],
                    'attributes' => [
                        'entityName' => 'John Doe'
                    ],
                    'relationships' => [
                        'entity' => ['data' => ['type' => 'users', 'id' => (string)$userId]]
                    ]
                ]
            ],
            'meta' => [
                'aggregatedData' => [
                    'lastNameCount' => [
                        ['value' => 'Doe', 'count' => 1]
                    ]
                ]
            ]
        ];
        self::assertResponseContent($expectedContent, $filteredResponseContent);
        self::assertCount(1, $filteredResponseContent['meta']['aggregatedData']['lastNameCount']);
    }

    public function testSearchWithAggregationOnly(): void
    {
        $userId = $this->getEntityId(User::class);
        $businessUnitId = $this->getEntityId(BusinessUnit::class);
        $organizationId = $this->getEntityId(Organization::class);
        $response = $this->cget(
            ['entity' => 'search'],
            [
                'filter' => [
                    'aggregations' => 'organization min firstOrganization',
                    'entities' => 'users,businessunits'
                ]
            ]
        );
        $filteredResponseContent = self::filterResponseContent($response);
        $expectedContent = [
            'data' => [
                [
                    'type' => 'search',
                    'id' => 'users-' . $userId,
                    'links' => [
                        'entityUrl' => $this->getUrl('oro_user_view', ['id' => $userId], true)
                    ],
                    'attributes' => [
                        'entityName' => 'John Doe'
                    ],
                    'relationships' => [
                        'entity' => ['data' => ['type' => 'users', 'id' => (string)$userId]]
                    ]
                ],
                [
                    'type' => 'search',
                    'id' => 'businessunits-' . $businessUnitId,
                    'links' => [
                        'entityUrl' => $this->getUrl('oro_business_unit_view', ['id' => $businessUnitId], true)
                    ],
                    'attributes' => [
                        'entityName' => 'Main'
                    ],
                    'relationships' => [
                        'entity' => ['data' => ['type' => 'businessunits', 'id' => (string)$businessUnitId]]
                    ]
                ]
            ],
            'meta' => [
                'aggregatedData' => [
                    'firstOrganization' => $organizationId
                ]
            ]
        ];
        self::assertResponseContent($expectedContent, $filteredResponseContent);
    }

    /**
     * @dataProvider invalidSearchQueryDataProvider
     */
    public function testTryToSearchByInvalidSearchQuery(string $searchQuery, string $errorMessage): void
    {
        $response = $this->cget(
            ['entity' => 'search'],
            ['filter' => ['searchQuery' => $searchQuery, 'entities' => 'users,businessunits']],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'filter constraint',
                'detail' => $errorMessage,
                'source' => ['parameter' => 'filter[searchQuery]']
            ],
            $response
        );
    }

    public static function invalidSearchQueryDataProvider(): array
    {
        return [
            [
                'lastName : Doe',
                'Not allowed operator. Unexpected token "punctuation" of value ":"'
                . ' ("operator" expected with value "~, !~, =, !=, in, !in, starts_with,'
                . ' exists, notexists, like, notlike") around position 10.'
            ],
            [
                'not_existing_field = Doe',
                'The field "not_existing_field" is not supported.'
            ],
            [
                'organization = a',
                'Invalid search query.'
            ],
            [
                'organization = 1a',
                'Unexpected string "a" in where statement around position 17.'
            ],
        ];
    }

    public function testTryToSearchWithInvalidAggregation(): void
    {
        $response = $this->cget(
            ['entity' => 'search'],
            ['filter' => ['aggregations' => 'organization unknown_function']],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'filter constraint',
                'detail' => 'The aggregating function "unknown_function" is not supported.',
                'source' => ['parameter' => 'filter[aggregations]']
            ],
            $response
        );
    }

    public function testSearchByFieldThatHaveSameNameForSeveralEntitiesButDifferentNameInSearchIndex(): void
    {
        $response = $this->cget(
            ['entity' => 'search'],
            ['filter[searchQuery]' => 'id = 1', 'filter[entities]' => 'users,businessunits']
        );
        $expectedContent = [
            'data' => [
                [
                    'type' => 'search',
                    'id' => 'businessunits-1',
                    'links' => [
                        'entityUrl' => $this->getUrl('oro_business_unit_view', ['id' => 1], true)
                    ],
                    'attributes' => [
                        'entityName' => 'Main'
                    ],
                    'relationships' => [
                        'entity' => ['data' => ['type' => 'businessunits', 'id' => '1']]
                    ]
                ],
                [
                    'type' => 'search',
                    'id' => 'users-1',
                    'links' => [
                        'entityUrl' => $this->getUrl('oro_user_view', ['id' => 1], true)
                    ],
                    'attributes' => [
                        'entityName' => 'John Doe'
                    ],
                    'relationships' => [
                        'entity' => ['data' => ['type' => 'users', 'id' => '1']]
                    ]
                ]
            ]
        ];
        $this->assertResponseContains($expectedContent, $response, true);
    }

    public function testAggregationByFieldThatHaveSameNameForSeveralEntitiesButDifferentNameInSearchIndex(): void
    {
        $response = $this->cget(
            ['entity' => 'search'],
            [
                'filter[aggregations]' => 'id count',
                'filter[searchQuery]' => 'id = 1',
                'filter[entities]' => 'users,businessunits'
            ]
        );
        $expectedContent = [
            'data' => [
                [
                    'type' => 'search',
                    'id' => 'businessunits-1',
                    'links' => [
                        'entityUrl' => $this->getUrl('oro_business_unit_view', ['id' => 1], true)
                    ],
                    'attributes' => [
                        'entityName' => 'Main'
                    ],
                    'relationships' => [
                        'entity' => ['data' => ['type' => 'businessunits', 'id' => '1']]
                    ]
                ],
                [
                    'type' => 'search',
                    'id' => 'users-1',
                    'links' => [
                        'entityUrl' => $this->getUrl('oro_user_view', ['id' => 1], true)
                    ],
                    'attributes' => [
                        'entityName' => 'John Doe'
                    ],
                    'relationships' => [
                        'entity' => ['data' => ['type' => 'users', 'id' => '1']]
                    ]
                ],
            ],
            'meta' => [
                'aggregatedData' => [
                    'idCount' => [
                        ['value' => 1, 'count' => 2]
                    ]
                ]
            ]
        ];
        $this->assertResponseContains($expectedContent, $response, true);
    }
}
