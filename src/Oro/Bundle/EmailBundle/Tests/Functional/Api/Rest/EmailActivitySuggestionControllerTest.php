<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\Api\Rest;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class EmailActivitySuggestionControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->markTestSkipped('Due to BAP-8365');

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
            'oro_api_get_activity_email_suggestions',
            ['id' => $this->getReference('email_1')->getId()]
        );
        $this->client->request('GET', $url);
        $entities = $this->getJsonResponseContent($this->client->getResponse(), 200);


        // 3 assigned users and 3 users from search result(not assigned).
        $this->assertCount(6, $entities);
    }

    public function testGetEntitiesWithPaging()
    {
        $url = $this->getUrl(
            'oro_api_get_activity_email_suggestions',
            ['id' => $this->getReference('email_1')->getId()]
        );
        $this->client->request(
            'GET',
            $url . '?page=3&limit=1',
            [],
            [],
            ['HTTP_X-Include' => 'totalCount']
        );
        $response = $this->client->getResponse();
        $entities = $this->getJsonResponseContent($response, 200);
        $this->assertCount(1, $entities);
        $this->assertArrayHasKey('assigned', reset($entities));

        // 3 assigned users and 3 users from search result(not assigned).
        $this->assertEquals(6, $response->headers->get('X-Include-Total-Count'));
    }

}
