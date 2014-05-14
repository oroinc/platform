<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\API;

use Oro\Bundle\TestFrameworkBundle\Test\Client;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class RestUsersACLTest extends WebTestCase
{
    const USER_NAME = 'user_wo_permissions';
    const USER_PASSWORD = 'user_api_key';

    const DEFAULT_USER_ID = '1';

    /**
     * @var Client
     */
    protected $client;

    protected static $hasLoaded = false;

    public function setUp()
    {
        $this->client = self::createClient(
            array(),
            $this->generateWsseAuthHeader(self::USER_NAME, self::USER_PASSWORD)
        );
        if (!self::$hasLoaded) {
            $this->client->appendFixtures(__DIR__ . DIRECTORY_SEPARATOR . 'DataFixtures');
        }
        self::$hasLoaded = true;
    }

    public function testCreateUser()
    {
        $request = array(
            "user" => array (
                "username" => 'user_' . mt_rand(),
                "email" => 'test_'  . mt_rand() . '@test.com',
                "enabled" => '1',
                "plainPassword" => '1231231q',
                "firstName" => "firstName",
                "lastName" => "lastName",
                "roles" => array("1")
            )
        );

        $this->client->request(
            'POST',
            $this->client->generate('oro_api_post_user'),
            $request,
            array(),
            $this->generateWsseAuthHeader(self::USER_NAME, self::USER_PASSWORD)
        );
        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 403);
    }

    public function testGetUsers()
    {
        //get user id
        $this->client->request(
            'GET',
            $this->client->generate('oro_api_get_users'),
            array('limit' => 100),
            array(),
            $this->generateWsseAuthHeader(self::USER_NAME, self::USER_PASSWORD)
        );
        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 403);
    }

    public function testGetUser()
    {
        //open user by id
        $this->client->request(
            'GET',
            $this->client->generate('oro_api_get_user', array('id' => self::DEFAULT_USER_ID)),
            array(),
            array(),
            $this->generateWsseAuthHeader(self::USER_NAME, self::USER_PASSWORD)
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
            $this->client->generate('oro_api_put_user', array('id' => self::DEFAULT_USER_ID)),
            $request,
            array(),
            $this->generateWsseAuthHeader(self::USER_NAME, self::USER_PASSWORD)
        );
        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 403);
    }

    public function testDeleteUser()
    {
        $this->client->request(
            'DELETE',
            $this->client->generate('oro_api_delete_user', array('id' => self::DEFAULT_USER_ID)),
            array(),
            array(),
            $this->generateWsseAuthHeader(self::USER_NAME, self::USER_PASSWORD)
        );
        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 403);
    }
}
