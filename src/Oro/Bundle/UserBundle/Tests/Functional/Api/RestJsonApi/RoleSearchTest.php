<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadAllRolesData;

class RoleSearchTest extends RestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([LoadAllRolesData::class]);
    }

    public function testSearchUserRoles(): void
    {
        $roleId = $this->getReference('role.role_administrator')->getId();
        $response = $this->cget(
            ['entity' => 'search'],
            ['filter' => ['entities' => 'userroles', 'searchText' => 'Administrator']]
        );
        $expectedContent = [
            'data' => [
                [
                    'type'          => 'search',
                    'id'            => 'userroles-' . $roleId,
                    'links'         => [
                        'entityUrl' => $this->getUrl('oro_user_role_view', ['id' => $roleId], true)
                    ],
                    'attributes'    => [
                        'entityName' => 'Administrator'
                    ],
                    'relationships' => [
                        'entity' => [
                            'data' => ['type' => 'userroles', 'id' => '<toString(@role.role_administrator->id)>']
                        ]
                    ]
                ]
            ]
        ];

        $this->assertResponseContains($expectedContent, $response);
    }
}
