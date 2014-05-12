<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\API;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Test\ToolsAPI;
use Oro\Bundle\TestFrameworkBundle\Test\Client;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class RestRolesTest extends WebTestCase
{
    /** @var Client */
    protected $client;

    public function setUp()
    {
        $this->client = static::createClient(array(), ToolsAPI::generateWsseHeader());
    }

    /**
     * @return array $request
     */
    public function testCreateRole()
    {
        $roleName = 'Role_' . mt_rand(100, 500);
        $request  = array(
            "role" => array(
                "label" => $roleName,
                "owner" => "1"
            )
        );
        $this->client->request('POST', $this->client->generate('oro_api_post_role'), $request);
        $result = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($result, 201);

        return $request;
    }

    /**
     * @depends testCreateRole
     *
     * @param array $request
     */
    public function testGetRoleByName($request)
    {
        $this->client->request(
            'GET',
            $this->client->generate('oro_api_get_role_byname', array('name' => $request['role']['label']))
        );
        $result = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($result, 200);
    }

    /**
     * @depends testCreateRole
     *
     * @param  array $request
     *
     * @return int   $roleId
     */
    public function testGetRoleById($request)
    {
        $this->client->request(
            'GET',
            $this->client->generate('oro_api_get_roles')
        );
        $result = $this->client->getResponse();
        $result = ToolsApi::jsonToArray($result->getContent());

        $role = array_filter(
            $result,
            function ($a) use ($request) {
                return $a['label'] === $request['role']['label'];
            }
        );
        $this->assertNotEmpty($role, 'Created role is not in roles list');

        $roleId = reset($role)['id'];

        $this->client->request(
            'GET',
            $this->client->generate('oro_api_get_role', array('id' => $roleId))
        );
        $result = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($result, 200);

        return $roleId;
    }

    /**
     * @depends testGetRoleById
     * @depends testCreateRole
     *
     * @param int $roleId
     * @param array $request
     */
    public function testUpdateRole($roleId, $request)
    {
        $request['role']['label'] .= '_Update';
        $this->client->request(
            'PUT',
            $this->client->generate('oro_api_put_role', array('id' => $roleId)),
            $request
        );
        $result = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($result, 204);

        $this->client->request(
            'GET',
            $this->client->generate('oro_api_get_role', array('id' => $roleId))
        );
        $result = $this->client->getResponse();
        $result = ToolsApi::jsonToArray($result->getContent());
        $this->assertEquals($result['label'], $request['role']['label'], 'Role does not updated');
    }

    /**
     * @depends testGetRoleById
     *
     * @param $roleId
     */
    public function testDeleteRole($roleId)
    {
        $this->client->request(
            'DELETE',
            $this->client->generate('oro_api_delete_role', array('id' => $roleId))
        );
        $result = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($result, 204);

        $this->client->request(
            'GET',
            $this->client->generate('oro_api_get_role', array('id' => $roleId))
        );
        $result = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($result, 404);
    }
}
