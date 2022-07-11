<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\Api\Rest;

use Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures\LoadEmailActivityData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class EmailActivityControllerTest extends WebTestCase
{
    /** @var string */
    private $baseUrl;

    protected function setUp(): void
    {
        $this->initClient([], self::generateWsseAuthHeader());
        $this->loadFixtures([LoadEmailActivityData::class]);
        $this->baseUrl = $this->getUrl('oro_api_get_email_activity_relations_by_filters');
    }

    public function testGetEntities()
    {
        $this->client->jsonRequest('GET', $this->baseUrl . '?messageId=email1@orocrm-pro.func-test');
        $entities = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertCount(3, $entities);
    }

    public function testGetEntitiesSeveralFilters()
    {
        $this->client->jsonRequest(
            'GET',
            $this->baseUrl . '?from=test1@example.com&to=test2@example.com&bcc=test4@example.com'
        );
        $entities = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertCount(1, $entities);
    }

    public function testGetEntitiesToFilterShouldWorkForToCcBcc()
    {
        $this->client->jsonRequest('GET', $this->baseUrl . '?to=test3@example.com');
        $entities = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertCount(1, $entities);
    }

    public function testGetEntitiesWithPaging()
    {
        $this->client->jsonRequest(
            'GET',
            $this->baseUrl . '?messageId=email1@orocrm-pro.func-test&page=2&limit=2',
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
        $this->client->jsonRequest('GET', $this->baseUrl);
        $this->getJsonResponseContent($this->client->getResponse(), 404);
    }

    public function testGetEntitiesMoreThanOneEmailFound()
    {
        $this->client->jsonRequest('GET', $this->baseUrl . '?from=test1@example.com');
        $this->getJsonResponseContent($this->client->getResponse(), 404);
    }

    public function testGetActivityTypes()
    {
        $this->client->jsonRequest(
            'GET',
            $this->getUrl('oro_api_get_activity_types')
        );
        $entities = $this->getJsonResponseContent($this->client->getResponse(), 200);
        // at least email activity should be returned
        $this->assertTrue(count($entities) >= 1);
    }

    public function testGetActivityTargetTypes()
    {
        $this->client->jsonRequest(
            'GET',
            $this->getUrl('oro_api_get_activity_target_types', ['activity' => 'emails'])
        );
        $entities = $this->getJsonResponseContent($this->client->getResponse(), 200);
        // at least User entity should be returned
        $this->assertTrue(count($entities) >= 1);
    }
}
