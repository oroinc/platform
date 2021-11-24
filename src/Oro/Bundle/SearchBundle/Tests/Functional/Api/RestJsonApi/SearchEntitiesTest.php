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

        return $filteredResponseContent;
    }

    private static function assertResponseContent(array $expectedContent, array $content): void
    {
        self::assertThat($content, new JsonApiDocContainsConstraint($expectedContent, false, false));
    }

    public function testGetList(): void
    {
        $response = $this->cget(['entity' => 'searchentities']);
        $filteredResponseContent = self::filterResponseContent($response);
        $expectedContent = [
            'data' => [
                [
                    'type'       => 'searchentities',
                    'id'         => 'users',
                    'attributes' => [
                        'entityType' => 'users',
                        'entityName' => 'User',
                        'searchable' => true
                    ]
                ],
                [
                    'type'       => 'searchentities',
                    'id'         => 'businessunits',
                    'attributes' => [
                        'entityType' => 'businessunits',
                        'entityName' => 'Business Unit',
                        'searchable' => false
                    ]
                ]
            ]
        ];
        self::assertResponseContent($expectedContent, $filteredResponseContent);
    }

    public function testGetListFilteredBySearchableTrue(): void
    {
        $response = $this->cget(['entity' => 'searchentities'], ['filter' => ['searchable' => true]]);
        $filteredResponseContent = self::filterResponseContent($response);
        $expectedContent = [
            'data' => [
                [
                    'type'       => 'searchentities',
                    'id'         => 'users',
                    'attributes' => [
                        'entityType' => 'users',
                        'entityName' => 'User',
                        'searchable' => true
                    ]
                ]
            ]
        ];
        self::assertResponseContent($expectedContent, $filteredResponseContent);
    }

    public function testGetListFilteredBySearchableFalse(): void
    {
        $response = $this->cget(['entity' => 'searchentities'], ['filter' => ['searchable' => false]]);
        $filteredResponseContent = self::filterResponseContent($response);
        $expectedContent = [
            'data' => [
                [
                    'type'       => 'searchentities',
                    'id'         => 'businessunits',
                    'attributes' => [
                        'entityType' => 'businessunits',
                        'entityName' => 'Business Unit',
                        'searchable' => false
                    ]
                ]
            ]
        ];
        self::assertResponseContent($expectedContent, $filteredResponseContent);
    }
}
