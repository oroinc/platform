<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\JsonApiDocContainsConstraint;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Test\Functional\RolePermissionExtension;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group search
 */
class EmailContextEntitiesTest extends RestJsonApiTestCase
{
    use RolePermissionExtension;

    private static function filterResponseContent(Response $response): array
    {
        $entityTypes = ['users'];
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
        $response = $this->cget(['entity' => 'emailcontextentities']);
        $filteredResponseContent = self::filterResponseContent($response);
        $expectedContent = [
            'data' => [
                [
                    'type'       => 'emailcontextentities',
                    'id'         => 'users',
                    'attributes' => [
                        'entityType' => 'users',
                        'entityName' => 'User',
                        'allowed'    => true
                    ]
                ]
            ]
        ];
        self::assertResponseContent($expectedContent, $filteredResponseContent);
    }

    public function testGetListFilteredByAllowedTrue(): void
    {
        $response = $this->cget(['entity' => 'emailcontextentities'], ['filter' => ['allowed' => true]]);
        $filteredResponseContent = self::filterResponseContent($response);
        $expectedContent = [
            'data' => [
                [
                    'type'       => 'emailcontextentities',
                    'id'         => 'users',
                    'attributes' => [
                        'entityType' => 'users',
                        'entityName' => 'User',
                        'allowed'    => true
                    ]
                ]
            ]
        ];
        self::assertResponseContent($expectedContent, $filteredResponseContent);
    }

    public function testGetListFilteredByAllowedFalse(): void
    {
        $response = $this->cget(['entity' => 'emailcontextentities'], ['filter' => ['allowed' => false]]);
        $filteredResponseContent = self::filterResponseContent($response);
        self::assertCount(0, $filteredResponseContent['data']);
    }

    public function testGetListFilteredByAllowedFalseWhenNoViewPermissionForTestedEntity(): void
    {
        $this->updateRolePermission('ROLE_ADMINISTRATOR', User::class, AccessLevel::NONE_LEVEL);
        $response = $this->cget(['entity' => 'emailcontextentities'], ['filter' => ['allowed' => false]]);
        $filteredResponseContent = self::filterResponseContent($response);
        $expectedContent = [
            'data' => [
                [
                    'type'       => 'emailcontextentities',
                    'id'         => 'users',
                    'attributes' => [
                        'entityType' => 'users',
                        'entityName' => 'User',
                        'allowed'    => false
                    ]
                ]
            ]
        ];
        self::assertResponseContent($expectedContent, $filteredResponseContent);
    }
}
