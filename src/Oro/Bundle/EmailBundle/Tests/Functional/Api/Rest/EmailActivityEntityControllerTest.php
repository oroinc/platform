<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\Api\Rest;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class EmailActivityEntityControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader());
        $this->loadFixtures(
            [
                'Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures\LoadEmailActivityData'
            ]
        );
    }

    public function testGetEntities()
    {
        $url = $this->getUrl(
            'oro_api_get_activity_relations',
            ['activity' => 'emails', 'id' => $this->getReference('email_1')->getId()]
        );
        $this->client->request('GET', $url);
        $entities = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertCount(3, $entities);
    }

    public function testGetEntitiesWithPaging()
    {
        $url = $this->getUrl(
            'oro_api_get_activity_relations',
            ['activity' => 'emails', 'id' => $this->getReference('email_1')->getId()]
        );
        $this->client->request(
            'GET',
            $url . '?page=2&limit=2',
            [],
            [],
            ['HTTP_X-Include' => 'totalCount']
        );
        $response = $this->client->getResponse();
        $entities = $this->getJsonResponseContent($response, 200);
        $this->assertCount(1, $entities);
        $this->assertEquals(3, $response->headers->get('X-Include-Total-Count'));
    }

    /**
     * @depends testGetEntities
     */
    public function testDeleteRelation()
    {
        $url = $this->getUrl(
            'oro_api_delete_activity_relation',
            [
                'activity' => 'emails',
                'id' => $this->getReference('email_1')->getId(),
                'entity' => 'users',
                'entityId' => $this->getReference('user_1')->getId()
            ]
        );
        $this->client->request('DELETE', $url);
        $this->assertEmptyResponseStatusCodeEquals($this->client->getResponse(), 204);

        // check that the relation was deleted
        $url = $this->getUrl(
            'oro_api_get_activity_relations',
            ['activity' => 'emails', 'id' => $this->getReference('email_1')->getId()]
        );
        $this->client->request('GET', $url);
        $entities = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertCount(2, $entities);
    }

    /**
     * @depends testDeleteRelation
     */
    public function testCreateRelation()
    {
        $url = $this->getUrl(
            'oro_api_post_activity_relation',
            ['activity' => 'emails', 'id' => $this->getReference('email_1')->getId()]
        );
        $this->client->request(
            'POST',
            $url,
            [
                'relations' => [
                    ['entity' => 'user', 'id' => $this->getReference('user_1')->getId()]
                ]
            ]
        );
        $this->assertEmptyResponseStatusCodeEquals($this->client->getResponse(), 204);

        // check that the relation was added
        $url = $this->getUrl(
            'oro_api_get_activity_relations',
            ['activity' => 'emails', 'id' => $this->getReference('email_1')->getId()]
        );
        $this->client->request('GET', $url);
        $entities = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertCount(3, $entities);
    }
}
