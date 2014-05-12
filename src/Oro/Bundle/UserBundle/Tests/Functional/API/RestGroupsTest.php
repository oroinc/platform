<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\API;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Test\ToolsAPI;
use Oro\Bundle\TestFrameworkBundle\Test\Client;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class RestGroupsTest extends WebTestCase
{
    /**
     * @var Client
     */
    protected $client;

    public function setUp()
    {
        $this->client = static::createClient(array(), ToolsAPI::generateWsseHeader());
    }

    /**
     * @return array
     */
    public function testCreateGroup()
    {
        $request = array(
            "group" => array(
                "name" => 'Group_'.mt_rand(100, 500),
                'owner' => '1'
            )
        );

        $this->client->request(
            'POST',
            $this->client->generate('oro_api_post_group'),
            $request
        );
        $result = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($result, 201);

        return $request;
    }

    /**
     * @depends testCreateGroup
     * @param  array $request
     * @return array $group
     */
    public function testGetGroups($request)
    {
        $this->client->request(
            'GET',
            $this->client->generate('oro_api_get_groups')
        );
        $result = $this->client->getResponse();
        $result = ToolsApi::jsonToArray($result->getContent());

        $group = array_filter(
            $result,
            function ($a) use ($request) {
                return $a['name'] === $request['group']['name'];
            }
        );
        $this->assertNotEmpty($group, 'Created group is not in groups list');

        return reset($group);
    }

    /**
     * @depends testCreateGroup
     * @depends testGetGroups
     * @param  array $request
     * @param  array $group
     * @return array $group
     */
    public function testUpdateGroup($request, $group)
    {
        $request['group']['name'] .= '_updated';
        $this->client->request(
            'PUT',
            $this->client->generate('oro_api_put_group', array('id' => $group['id'])),
            $request
        );
        $result = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($result, 204);

        $this->client->request(
            'GET',
            $this->client->generate('oro_api_get_group', array('id' => $group['id']))
        );
        $result = $this->client->getResponse();
        $result = ToolsApi::jsonToArray($result->getContent());
        $this->assertArrayHasKey('name', $result);
        $this->assertEquals($result['name'], $request['group']['name'], 'Group does not updated');

        return $group;
    }

    /**
     * @depends testUpdateGroup
     * @param $group
     */
    public function testDeleteGroup($group)
    {
        $this->client->request(
            'DELETE',
            $this->client->generate('oro_api_delete_group', array('id' => $group['id']))
        );
        $result = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($result, 204);

        $this->client->request(
            'GET',
            $this->client->generate('oro_api_get_group', array('id' => $group['id']))
        );
        $result = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($result, 404);
    }
}
