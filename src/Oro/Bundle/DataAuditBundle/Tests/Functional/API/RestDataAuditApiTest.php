<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Functional\API;

use Oro\Bundle\TestFrameworkBundle\Test\Client;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class RestDataAuditApiTest extends WebTestCase
{

    /** @var Client */
    protected $client;

    public function setUp()
    {
        $this->client = self::createClient(array(), $this->generateWsseAuthHeader());
    }

    /**
     * @return array
     */
    public function testPreconditions()
    {
        // create users
        $request = array(
            "user" => array (
                "username" => 'user_' . mt_rand(),
                "email" => 'test_'  . mt_rand() . '@test.com',
                "enabled" => '1',
                "plainPassword" => '1231231q',
                "namePrefix" => "Mr",
                "firstName" => "firstName",
                "middleName" => "middleName",
                "lastName" => "lastName",
                "nameSuffix" => "Sn.",
                "roles" => array("2"),
                "owner" => "1"
            )
        );

        $this->client->request('POST', $this->client->generate('oro_api_post_user'), $request);
        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 201);

        return $request;
    }

    /**
     * @param array $response
     * @return array
     * @depends testPreconditions
     */
    public function testGetAudits(array $response)
    {
        $this->client->request(
            'GET',
            $this->client->generate('oro_api_get_audits')
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $resultActual = reset($result);

        $this->assertEquals('create', $resultActual['action']);
        $this->assertEquals('Oro\Bundle\UserBundle\Entity\User', $resultActual['object_class']);
        $this->assertEquals($response['user']['username'], $resultActual['object_name']);
        $this->assertEquals('admin', $resultActual['username']);
        $this->assertEquals($response['user']['username'], $resultActual['data']['username']['new']);
        $this->assertEquals($response['user']['email'], $resultActual['data']['email']['new']);
        $this->assertEquals($response['user']['enabled'], $resultActual['data']['enabled']['new']);
        $this->assertContains($resultActual['data']['roles']['new'], array('User', 'Sales Rep'));

        return $result;
    }

    /**
     * @param  array $response
     * @depends testGetAudits
     */
    public function testGetAudit(array $response)
    {
        foreach ($response as $audit) {
            $this->client->request(
                'GET',
                $this->client->generate('oro_api_get_audit', array('id' => $audit['id']))
            );

            $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

            unset($result['loggedAt']);
            unset($audit['loggedAt']);

            $this->assertEquals($audit, $result);
        }
    }
}
