<?php

namespace Oro\Bundle\SearchBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\JsonApiDocContainsConstraint;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\UserBundle\Entity\User;
use PHPUnit\Framework\ExpectationFailedException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Yaml\Yaml;

/**
 * @group search
 */
class SearchTest extends RestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // do the reindex because by some unknown reasons the search index is empty
        // after upgrade from old application version
        $indexer = self::getContainer()->get('oro_search.search.engine.indexer');
        $indexer->reindex(User::class);
        $indexer->reindex(BusinessUnit::class);
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
        return self::getContainer()
            ->get('doctrine')
            ->getRepository($entityClass)
            ->createQueryBuilder('e')
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
                    'type'          => 'search',
                    'id'            => 'users-' . $userId,
                    'links'         => [
                        'entityUrl' => $this->getUrl('oro_user_view', ['id' => $userId], true)
                    ],
                    'attributes'    => [
                        'entityName' => 'John Doe'
                    ],
                    'relationships' => [
                        'entity' => ['data' => ['type' => 'users', 'id' => (string)$userId]]
                    ]
                ],
                [
                    'type'          => 'search',
                    'id'            => 'businessunits-' . $businessUnitId,
                    'links'         => [
                        'entityUrl' => $this->getUrl('oro_business_unit_view', ['id' => $businessUnitId], true)
                    ],
                    'attributes'    => [
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
                    'type'          => 'search',
                    'id'            => 'businessunits-' . $businessUnitId,
                    'links'         => [
                        'entityUrl' => $this->getUrl('oro_business_unit_view', ['id' => $businessUnitId], true)
                    ],
                    'attributes'    => [
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
                    'type'          => 'search',
                    'id'            => 'users-' . $userId,
                    'links'         => [
                        'entityUrl' => $this->getUrl('oro_user_view', ['id' => $userId], true)
                    ],
                    'attributes'    => [
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
            'data'     => [
                [
                    'type'          => 'search',
                    'id'            => 'users-' . $userId,
                    'links'         => [
                        'entityUrl' => $this->getUrl('oro_user_view', ['id' => $userId], true)
                    ],
                    'attributes'    => [
                        'entityName' => 'John Doe'
                    ],
                    'relationships' => [
                        'entity' => ['data' => ['type' => 'users', 'id' => (string)$userId]]
                    ]
                ],
                [
                    'type'          => 'search',
                    'id'            => 'businessunits-' . $businessUnitId,
                    'links'         => [
                        'entityUrl' => $this->getUrl('oro_business_unit_view', ['id' => $businessUnitId], true)
                    ],
                    'attributes'    => [
                        'entityName' => 'Main'
                    ],
                    'relationships' => [
                        'entity' => ['data' => ['type' => 'businessunits', 'id' => (string)$businessUnitId]]
                    ]
                ]
            ],
            'included' => [
                [
                    'type'       => 'users',
                    'id'         => (string)$userId,
                    'attributes' => [
                        'username' => 'admin'
                    ]
                ],
                [
                    'type'       => 'businessunits',
                    'id'         => (string)$businessUnitId,
                    'attributes' => [
                        'name' => 'Main'
                    ]
                ]
            ]
        ];
        self::assertResponseContent($expectedContent, $filteredResponseContent);
    }
}
