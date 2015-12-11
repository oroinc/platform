<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\API;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class RestUsersTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader());
    }

    /**
     * @return array
     */
    public function testCreateUser()
    {
        $request = [
            "user" => [
                "username"      => 'user_' . mt_rand(),
                "email"         => 'test_' . mt_rand() . '@test.com',
                "phone"         => '123-123',
                "enabled"       => '1',
                "plainPassword" => '1231231q',
                "firstName"     => "firstName",
                "lastName"      => "lastName",
                "roles"         => ["3"],
                "owner"         => "1"
            ]
        ];
        $this->client->request(
            'POST',
            $this->getUrl('oro_api_post_user'),
            $request
        );
        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 201);

        return $request;
    }

    /**
     * Test validation.
     * Wrong request - "plainPassword" is NOT specified.
     *
     * @return array
     */
    public function testCreateUserFailed()
    {
        $request = [
            'user' => [
                'username'  => 'user_' . mt_rand(),
                'firstName' => "firstName",
                'lastName'  => "lastName",
                'email'     => 'test_' . mt_rand() . '@test . com',
                'enabled'   => '1'
            ]
        ];

        $this->client->request('POST', $this->getUrl('oro_api_post_user'), $request);
        $result = $this->client->getResponse();

        /**
         * "Validation Failed",
         * "plainPassword": {
         *   "errors": [
         *     "This value should not be blank."
         *   ]
         * },
         */
        $this->assertJsonResponseStatusCodeEquals($result, 400);
        $message = json_decode($result->getContent())->message;
        $this->assertEquals('Validation Failed', $message);

        return $request;
    }

    /**
     * @depends testCreateUser
     */
    public function testGetUsers()
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_users')
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertCount(2, $result);
    }

    /**
     * @depends testCreateUser
     */
    public function testGetUsersFilteredByPhone()
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_users', ['phone' => '123-123'])
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertCount(1, $result);
        $this->assertEquals('123-123', $result[0]['phone']);
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
            $this->getUrl('oro_api_get_users'),
            ['limit' => 100]
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $userId = $this->assertEqualsUser($request, $result);

        //update user
        $request['user']['username'] .= '_Updated';
        unset($request['user']['plainPassword']);
        $this->client->request(
            'PUT',
            $this->getUrl('oro_api_put_user', ['id' => $userId]),
            $request
        );
        $result = $this->client->getResponse();
        $this->assertEmptyResponseStatusCodeEquals($result, 204);

        //open user by id
        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_user', ['id' => $userId])
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
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
            $this->getUrl(
                'oro_api_get_user_filter',
                [
                    'username' => $request['user']['username'],
                    'email'    => $request['user']['email']
                ]
            )
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertEquals($request['user']['firstName'], $result['firstName']);
        $this->assertEquals($request['user']['lastName'], $result['lastName']);
    }

    public function testFilterUserNonExist()
    {
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_api_get_user_filter'
            )
        );
        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 400);
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
            $this->getUrl('oro_api_delete_user', ['id' => $userId])
        );
        $result = $this->client->getResponse();
        $this->assertEmptyResponseStatusCodeEquals($result, 204);

        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_user', ['id' => $userId])
        );
        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 404);
    }

    public function testSelfDeleteUser()
    {
        $this->client->request(
            'DELETE',
            $this->getUrl('oro_api_delete_user', ['id' => 1])
        );
        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 403);
    }

    public function testGetUserRoles()
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_user_roles', ['id' => 1])
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertEquals('Administrator', reset($result)['label']);
    }

    public function testGetUserRolesNotFound()
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_user_roles', ['id' => 0])
        );
        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 404);
    }

    public function testGetUserGroups()
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_user_groups', ['id' => 1])
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertEquals('Administrators', reset($result)['name']);
    }

    public function testGetUserGroupsNotFound()
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_user_groups', ['id' => 0])
        );
        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 404);
    }
}
