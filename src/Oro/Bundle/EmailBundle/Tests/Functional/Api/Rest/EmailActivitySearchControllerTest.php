<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\Api\Rest;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class EmailActivitySearchControllerTest extends WebTestCase
{
    /** @var string */
    protected $baseUrl;

    protected function setUp()
    {
        $this->markTestSkipped('Due to BAP-8365');

        $this->initClient([], $this->generateWsseAuthHeader());
        $this->loadFixtures(
            [
                'Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures\LoadEmailActivityData'
            ]
        );
        $this->baseUrl = $this->getUrl('oro_api_get_email_search_relations');
    }

    public function testEmailSearch()
    {
        $entityClasses = [];

        // No search string - should return all entities:
        // 3 user loaded by data fixture + admin + 2 simple users from user dependencies fixture
        $this->client->request('GET', $this->baseUrl);
        $entities = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertNotEmpty($entities);
        $this->assertCount(6, $entities);
        foreach ($entities as $entity) {
            if (!isset($entityClasses[$entity['entity']])) {
                $entityClasses[$entity['entity']] = $entity['entity'];
            }
        }
        // Check using multiple entities in from filter. Should return all entities.
        $this->client->request('GET', $this->baseUrl . sprintf('?from=%s', implode(',', $entityClasses)));
        $this->assertCount(count($entities), $this->getJsonResponseContent($this->client->getResponse(), 200));

        // Check search by user name
        $this->client->request('GET', $this->baseUrl . '?search=Richard');
        $entities = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertCount(1, $entities);

        // Check search by user name filtered by User entity only
        $this->client->request(
            'GET',
            $this->baseUrl . '?search=Richard&from=Oro\Bundle\UserBundle\Entity\User'
        );
        $entities = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertCount(1, $entities);

        // Check searching by non-existing user name. Should return no results.
        $this->client->request('GET', $this->baseUrl . sprintf('?search=%s&page=1', 'NonExistentEntityTitle'));
        $entities = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertEmpty($entities);
    }

    public function testEmailSearchWithEmailFilter()
    {
        // Check search by email
        $this->client->request('GET', $this->baseUrl . '?email=richard_bradley@example.com');
        $entities = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertCount(1, $entities);

        // Check search by 2 emails
        $emails = ['richard_bradley@example.com', 'brenda_brock@example.com'];
        $this->client->request('GET', $this->baseUrl . '?email=' . implode(',', $emails));
        $entities = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertCount(2, $entities);

        // Check search by email filtered by User entity only
        $this->client->request(
            'GET',
            $this->baseUrl . '?email=richard_bradley@example.com&from=Oro\Bundle\UserBundle\Entity\User'
        );
        $entities = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertCount(1, $entities);

        // Check searching by non-existing email. Should return no results.
        $this->client->request('GET', $this->baseUrl . sprintf('?email=%s&page=1', 'non_existent_email@example.com'));
        $entities = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertEmpty($entities);
    }

    public function testEmailSearchWithEmailAndSearchFilter()
    {
        $this->markTestSkipped('Due to BAP-8497');

        // Check search by user name and email
        $this->client->request(
            'GET',
            $this->baseUrl,
            [
                'search' => 'Richard',
                'email'  => 'richard_bradley@example.com'
            ]
        );
        $entities = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertCount(1, $entities);

        // Check search by user name and another email
        $this->client->request(
            'GET',
            $this->baseUrl,
            [
                'search' => 'Brenda',
                'email'  => 'richard_bradley@example.com'
            ]
        );
        $entities = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertEmpty($entities);
    }

    public function testEmailSearchWithPaging()
    {
        $this->client->request(
            'GET',
            $this->baseUrl . '?page=2&limit=2',
            [],
            [],
            ['HTTP_X-Include' => 'totalCount']
        );
        $response = $this->client->getResponse();
        $entities = $this->getJsonResponseContent($response, 200);
        $this->assertCount(2, $entities);
        $this->assertEquals(6, $response->headers->get('X-Include-Total-Count'));
    }
}
