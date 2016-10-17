<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\API;

use Oro\Bundle\UserBundle\Tests\Functional\API\DataFixtures\LoadUserData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class RestUsersACLTest extends WebTestCase
{
    const DEFAULT_USER_ID = '1';

    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures(array('Oro\Bundle\UserBundle\Tests\Functional\API\DataFixtures\LoadUserData'));
    }

    public function testCreateUser()
    {
        $request = array(
            "user" => array (
                "username" => 'user_' . mt_rand(),
                "email" => 'test_'  . mt_rand() . '@test.com',
                "enabled" => '1',
                "plainPassword" => '1231231Q',
                "firstName" => "firstName",
                "lastName" => "lastName",
                "roles" => array("1")
            )
        );

        $this->client->request(
            'POST',
            $this->getUrl('oro_api_post_user'),
            $request,
            array(),
            $this->generateWsseAuthHeader(LoadUserData::USER_NAME, LoadUserData::USER_PASSWORD)
        );
        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 403);
    }

    public function testGetUsers()
    {
        //get user id
        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_users'),
            array('limit' => 100),
            array(),
            $this->generateWsseAuthHeader(LoadUserData::USER_NAME, LoadUserData::USER_PASSWORD)
        );
        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 403);
    }

    public function testGetUser()
    {
        //open user by id
        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_user', array('id' => self::DEFAULT_USER_ID)),
            array(),
            array(),
            $this->generateWsseAuthHeader(LoadUserData::USER_NAME, LoadUserData::USER_PASSWORD)
        );
        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 403);
    }

    public function testUpdateUser()
    {
        $request = array(
            "user" => array (
                "username" => 'user_' . mt_rand(),
                "email" => 'test_'  . mt_rand() . '@test.com',
                "enabled" => '1',
                "firstName" => "firstName",
                "lastName" => "lastName",
                "roles" => array("1")
            )
        );

        $this->client->request(
            'PUT',
            $this->getUrl('oro_api_put_user', array('id' => self::DEFAULT_USER_ID)),
            $request,
            array(),
            $this->generateWsseAuthHeader(LoadUserData::USER_NAME, LoadUserData::USER_PASSWORD)
        );
        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 403);
    }

    public function testDeleteUser()
    {
        $this->client->request(
            'DELETE',
            $this->getUrl('oro_api_delete_user', array('id' => self::DEFAULT_USER_ID)),
            array(),
            array(),
            $this->generateWsseAuthHeader(LoadUserData::USER_NAME, LoadUserData::USER_PASSWORD)
        );
        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 403);
    }
}
