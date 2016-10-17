<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\API;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Tests\Functional\API\DataFixtures\LoadUserData;

/**
 * @dbIsolation
 */
class RestInvalidUsersTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
    }

    public function testInvalidKey()
    {
        $request = array(
            "user" => array (
                "username" => 'user_' . mt_rand(),
                "email" => 'test_'  . mt_rand() . '@test.com',
                "enabled" => 'true',
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
            array(),
            $this->generateWsseAuthHeader()
        );
        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 401);
    }

    public function testInvalidUser()
    {
        $request = array(
            "user" => array (
                "username" => 'user_' . mt_rand(),
                "email" => 'test_'  . mt_rand() . '@test.com',
                "enabled" => 'true',
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
            array(),
            $this->generateWsseAuthHeader(LoadUserData::USER_NAME, LoadUserData::USER_PASSWORD)
        );
        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 401);
    }
}
