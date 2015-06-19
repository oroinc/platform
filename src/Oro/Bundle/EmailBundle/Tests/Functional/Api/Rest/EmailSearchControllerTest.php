<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\Api\Rest;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class EmailSearchControllerTest extends WebTestCase
{
    /** @var string */
    protected $baseUrl;

    protected function setUp()
    {
        $this->markTestSkipped('Due to BAP-8365');

        $this->initClient([], $this->generateWsseAuthHeader());
        $this->loadFixtures(
            [
                'Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures\LoadEmailSearchData'
            ]
        );
        $this->baseUrl = $this->getUrl('oro_api_get_email_search_relations');
    }

    public function testEmailSearchByEmail()
    {
        $this->client->request('GET', $this->baseUrl . '?email=test1@example.com');
        $entities = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertCount(1, $entities);
    }

    public function testNonExistingEmail()
    {
        $this->client->request('GET', $this->baseUrl . '?email=test5@example.com');
        $entities = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertEmpty($entities);
    }

    public function testEmailSearchWithoutFrom()
    {
        // No search string - should return all entities:
        // 3 user loaded by data fixture + 1 admin
        $this->client->request('GET', $this->baseUrl);
        $entities = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertNotEmpty($entities);
        $this->assertCount(4, $entities);
    }

    public function testEmailSearchWithFrom()
    {
        // Check search by user name filtered by User entity only
        $this->client->request(
            'GET',
            $this->baseUrl . '?from=Oro\Bundle\UserBundle\Entity\User'
        );
        $entities = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertCount(4, $entities);
    }

    public function testEmailSearchWithPaging()
    {
        $this->client->request(
            'GET',
            $this->baseUrl . '?page=2&limit=3',
            [],
            [],
            ['HTTP_X-Include' => 'totalCount']
        );
        $response = $this->client->getResponse();
        $entities = $this->getJsonResponseContent($response, 200);
        $this->assertCount(1, $entities);
        $this->assertEquals(4, $response->headers->get('X-Include-Total-Count'));
    }
}
