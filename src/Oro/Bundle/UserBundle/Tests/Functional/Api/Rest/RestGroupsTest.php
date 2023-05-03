<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\Api\Rest;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class RestGroupsTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], self::generateWsseAuthHeader());
    }

    public function testCreateGroup(): array
    {
        $request = [
            'group' => [
                'name' => 'Group_' . random_int(100, 500),
                'owner' => '1'
            ]
        ];

        $this->client->jsonRequest(
            'POST',
            $this->getUrl('oro_api_post_group'),
            $request
        );
        $result = $this->client->getResponse();
        self::assertJsonResponseStatusCodeEquals($result, 201);

        return $request;
    }

    /**
     * @depends testCreateGroup
     */
    public function testGetGroups(array $request): array
    {
        $this->client->jsonRequest(
            'GET',
            $this->getUrl('oro_api_get_groups')
        );

        $result = self::getJsonResponseContent($this->client->getResponse(), 200);

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
     */
    public function testUpdateGroup(array $request, array $group): array
    {
        $request['group']['name'] .= '_updated';
        $this->client->jsonRequest(
            'PUT',
            $this->getUrl('oro_api_put_group', ['id' => $group['id']]),
            $request
        );
        $result = $this->client->getResponse();
        self::assertEmptyResponseStatusCodeEquals($result, 204);

        $this->client->jsonRequest(
            'GET',
            $this->getUrl('oro_api_get_group', ['id' => $group['id']])
        );

        $result = self::getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertArrayHasKey('name', $result);
        $this->assertEquals($result['name'], $request['group']['name'], 'Group does not updated');

        return $group;
    }

    /**
     * @depends testUpdateGroup
     */
    public function testDeleteGroup(array $group)
    {
        $this->client->jsonRequest(
            'DELETE',
            $this->getUrl('oro_api_delete_group', ['id' => $group['id']])
        );
        $result = $this->client->getResponse();
        self::assertEmptyResponseStatusCodeEquals($result, 204);

        $this->client->jsonRequest(
            'GET',
            $this->getUrl('oro_api_get_group', ['id' => $group['id']])
        );
        $result = $this->client->getResponse();
        self::assertJsonResponseStatusCodeEquals($result, 404);
    }
}
