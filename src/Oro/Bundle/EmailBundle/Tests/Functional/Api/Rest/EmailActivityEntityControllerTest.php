<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\Api\Rest;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class EmailActivityEntityControllerTest extends WebTestCase
{
    /** @var string */
    protected $baseUrl;

    protected function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader());
        $this->loadFixtures(
            [
                'Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures\LoadEmailActivityData'
            ]
        );
        $this->baseUrl = $this->getUrl('oro_api_get_activity_relations') . '?activity_type=email';
    }

    public function testGetEntities()
    {
        $this->client->request('GET', $this->baseUrl . '&activity_id=' . $this->getReference('email_1')->getId());
        $entities = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertCount(3, $entities);
    }

    public function testGetEntitiesWithPaging()
    {
        $this->client->request(
            'GET',
            $this->baseUrl . '&page=2&limit=2&activity_id=' . $this->getReference('email_1')->getId(),
            [],
            [],
            ['HTTP_X-Include' => 'totalCount']
        );
        $response = $this->client->getResponse();
        $entities = $this->getJsonResponseContent($response, 200);
        $this->assertCount(1, $entities);
        $this->assertEquals(3, $response->headers->get('X-Include-Total-Count'));
    }
}
