<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\Api\Rest;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class EmailActivityControllerTest extends WebTestCase
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
        $this->baseUrl = $this->getUrl('oro_api_get_email_activity_relations_by_filters');
    }

    public function testGetEntities()
    {
        $this->client->request('GET', $this->baseUrl . '?messageId=email1@orocrm-pro.func-test');
        $entities = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertCount(3, $entities);
    }

    public function testGetEntitiesSeveralFilters()
    {
        $this->client->request(
            'GET',
            $this->baseUrl . '?from=test1@example.com&to=test2@example.com&bcc=test4@example.com'
        );
        $entities = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertCount(1, $entities);
    }

    public function testGetEntitiesToFilterShouldWorkForToCcBcc()
    {
        $this->client->request('GET', $this->baseUrl . '?to=test3@example.com');
        $entities = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertCount(1, $entities);
    }

    public function testGetEntitiesWithPaging()
    {
        $this->client->request(
            'GET',
            $this->baseUrl . '?messageId=email1@orocrm-pro.func-test&page=2&limit=2',
            [],
            [],
            ['HTTP_X-Include' => 'totalCount']
        );
        $response = $this->client->getResponse();
        $entities = $this->getJsonResponseContent($response, 200);
        $this->assertCount(1, $entities);
        $this->assertEquals(3, $response->headers->get('X-Include-Total-Count'));
    }

    public function testGetEntitiesNoFilters()
    {
        $this->client->request('GET', $this->baseUrl);
        $this->getJsonResponseContent($this->client->getResponse(), 404);
    }

    public function testGetEntitiesMoreThanOneEmailFound()
    {
        $this->client->request('GET', $this->baseUrl . '?from=test1@example.com');
        $this->getJsonResponseContent($this->client->getResponse(), 404);
    }

    public function testGetActivityTypes()
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_activity_types')
        );
        $entities = $this->getJsonResponseContent($this->client->getResponse(), 200);
        // at least email activity should be returned
        $this->assertTrue(count($entities) >= 1);
    }

    public function testGetActivityTargetTypes()
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_activity_target_types', ['activity' => 'emails'])
        );
        $entities = $this->getJsonResponseContent($this->client->getResponse(), 200);
        // at least User entity should be returned
        $this->assertTrue(count($entities) >= 1);
    }
}
