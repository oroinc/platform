<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\API;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Test\ToolsAPI;
use Oro\Bundle\TestFrameworkBundle\Test\Client;

/**
 * @outputBuffering enabled
 * @db_isolation
 */
class RestUsersTest extends WebTestCase
{
    /** @var Client */
    protected $client;

    public function setUp()
    {
        $this->client = static::createClient(array(), ToolsAPI::generateWsseHeader());
    }

    /**
     * @return array
     */
    public function testCreateUser()
    {
        $request = array(
            "user" => array(
                "username"      => 'user_' . mt_rand(),
                "email"         => 'test_' . mt_rand() . '@test.com',
                "enabled"       => '1',
                "plainPassword" => '1231231q',
                "firstName"     => "firstName",
                "lastName"      => "lastName",
                "roles"         => array("3"),
                "owner"         => "1",
                "inviteUser"    => false
            )
        );
        $this->client->request(
            'POST',
            $this->client->generate('oro_api_post_user'),
            $request
        );
        $result = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($result);

        return $request;
    }

    /**
     * @depends testCreateUser
     *
     * @param array $request
     *
     * @return int
     */
    public function testUpdateUser($request)
    {
        //get user id
        $this->client->request(
            'GET',
            $this->client->generate('oro_api_get_users'),
            array('limit' => 100)
        );
        $result = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($result, 200);
        $result = ToolsAPI::jsonToArray($result->getContent());
        $userId = $this->assertEqualsUser($request, $result);

        //update user
        $request['user']['username'] .= '_Updated';
        unset($request['user']['plainPassword']);
        $this->client->request(
            'PUT',
            $this->client->generate('oro_api_put_user', array('id' => $userId)),
            $request
        );
        $result = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($result, 204);

        //open user by id
        $this->client->request(
            'GET',
            $this->client->generate('oro_api_get_user', array('id' => $userId))
        );
        $result = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($result, 200);

        $result = ToolsAPI::jsonToArray($result->getContent());
        //compare result
        $this->assertEquals($request['user']['username'], $result['username']);

        return $userId;
    }

    /**
     * Check created user
     *
     * @return int
     *
     * @param  array $result
     * @param  array $request
     */
    protected function assertEqualsUser($request, $result)
    {
        $flag = 1;
        foreach ($result as $key => $object) {
            foreach ($request as $user) {
                if ($user['username'] == $result[$key]['username']) {
                    $flag   = 0;
                    $userId = $result[$key]['id'];
                    break 2;
                }
            }
        }
        $this->assertEquals(0, $flag);

        return $userId;
    }

    /**
     * @param array $request
     *
     * @depends testCreateUser
     * @depends testUpdateUser
     */
    public function testFilterUser($request)
    {
        $request['user']['username'] .= '_Updated';
        //get user
        $this->client->request(
            'GET',
            $this->client->generate(
                'oro_api_get_user_filter',
                array(
                    'username' => $request['user']['username'],
                    'email'    => $request['user']['email']
                )
            )
        );
        $result = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($result, 200);
        $result = ToolsAPI::jsonToArray($result->getContent());
        $this->assertEquals($request['user']['firstName'], $result['firstName']);
        $this->assertEquals($request['user']['lastName'], $result['lastName']);
    }

    public function testFilterUserNonExist()
    {
        $this->client->request(
            'GET',
            $this->client->generate(
                'oro_api_get_user_filter'
            )
        );
        $result = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($result, 400);
    }

    /**
     * @depends testUpdateUser
     *
     * @param int $userId
     */
    public function testDeleteUser($userId)
    {
        $this->client->request(
            'DELETE',
            $this->client->generate('oro_api_delete_user', array('id' => $userId))
        );
        $result = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($result, 204);

        $this->client->request(
            'GET',
            $this->client->generate('oro_api_get_user', array('id' => $userId))
        );
        $result = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($result, 404);
    }

    public function testSelfDeleteUser()
    {
        $this->client->request(
            'DELETE',
            $this->client->generate('oro_api_delete_user', array('id' => 1))
        );
        $result = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($result, 403);
    }

    public function testGetUserRoles()
    {
        $this->client->request(
            'GET',
            $this->client->generate('oro_api_get_user_roles', array('id' => 1))
        );
        $result = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($result, 200);
        $result = ToolsAPI::jsonToArray($result->getContent());
        $this->assertEquals('Administrator', reset($result)['label']);
    }

    public function testGetUserRolesNotFound()
    {
        $this->client->request(
            'GET',
            $this->client->generate('oro_api_get_user_roles', array('id' => 0))
        );
        $result = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($result, 404);
    }

    public function testGetUserGroups()
    {
        $this->client->request(
            'GET',
            $this->client->generate('oro_api_get_user_groups', array('id' => 1))
        );
        $result = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($result, 200);
        $result = ToolsAPI::jsonToArray($result->getContent());
        $this->assertEquals('Administrators', reset($result)['name']);
    }

    public function testGetUserGroupsNotFound()
    {
        $this->client->request(
            'GET',
            $this->client->generate('oro_api_get_user_groups', array('id' => 0))
        );
        $result = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($result, 404);
    }
}
