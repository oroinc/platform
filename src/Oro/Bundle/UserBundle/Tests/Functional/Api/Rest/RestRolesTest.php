<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\Api\Rest;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class RestRolesTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], self::generateWsseAuthHeader());
    }

    public function testCreateRole(): array
    {
        $roleName = 'Role_' . random_int(100, 500);
        $request  = [
            'role' => [
                'label' => $roleName,
            ]
        ];
        $this->client->jsonRequest('POST', $this->getUrl('oro_api_post_role'), $request);
        $result = $this->client->getResponse();
        self::assertJsonResponseStatusCodeEquals($result, 201);

        return $request;
    }

    /**
     * @depends testCreateRole
     */
    public function testGetRoleByName(array $request)
    {
        $this->client->jsonRequest(
            'GET',
            $this->getUrl('oro_api_get_role_byname', ['name' => $request['role']['label']])
        );
        $result = $this->client->getResponse();
        self::assertJsonResponseStatusCodeEquals($result, 200);
    }

    /**
     * @depends testCreateRole
     */
    public function testGetRoleById(array $request): int
    {
        $this->client->jsonRequest(
            'GET',
            $this->getUrl('oro_api_get_roles', ['limit' => 20])
        );

        $result = self::getJsonResponseContent($this->client->getResponse(), 200);

        $role = array_filter(
            $result,
            function ($a) use ($request) {
                return $a['label'] === $request['role']['label'];
            }
        );
        $this->assertNotEmpty($role, 'Created role is not in roles list');

        $roleId = reset($role)['id'];

        $this->client->jsonRequest(
            'GET',
            $this->getUrl('oro_api_get_role', ['id' => $roleId])
        );
        $result = $this->client->getResponse();
        self::assertJsonResponseStatusCodeEquals($result, 200);

        return $roleId;
    }

    /**
     * @depends testGetRoleById
     * @depends testCreateRole
     */
    public function testUpdateRole(int $roleId, array $request)
    {
        $request['role']['label'] .= '_Update';
        $this->client->jsonRequest(
            'PUT',
            $this->getUrl('oro_api_put_role', ['id' => $roleId]),
            $request
        );
        $result = $this->client->getResponse();
        self::assertEmptyResponseStatusCodeEquals($result, 204);

        $this->client->jsonRequest(
            'GET',
            $this->getUrl('oro_api_get_role', ['id' => $roleId])
        );

        $result = self::getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertEquals($result['label'], $request['role']['label'], 'Role does not updated');
    }

    /**
     * @depends testGetRoleById
     */
    public function testDeleteRole(int $roleId)
    {
        $this->client->jsonRequest(
            'DELETE',
            $this->getUrl('oro_api_delete_role', ['id' => $roleId])
        );
        $result = $this->client->getResponse();
        self::assertEmptyResponseStatusCodeEquals($result, 204);

        $this->client->jsonRequest(
            'GET',
            $this->getUrl('oro_api_get_role', ['id' => $roleId])
        );
        $result = $this->client->getResponse();
        self::assertJsonResponseStatusCodeEquals($result, 404);
    }
}
