<?php

namespace Oro\Bundle\ActivityBundle\Tests\Functional\Controller\Api\Rest;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroCRMPro\Bundle\OutlookBundle\Tests\Functional\DataFixtures\LoadOutlookEntitiesData;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class ActivitySearchControllerTest extends WebTestCase
{
    /** @var string */
    protected $baseUrl;

    protected function setUp()
    {
        $this->markTestSkipped('Due to BAP-8365');

        $this->initClient([], $this->generateWsseAuthHeader());
        $this->loadFixtures(['OroCRMPro\Bundle\OutlookBundle\Tests\Functional\DataFixtures\LoadOutlookEntitiesData']);
        $this->baseUrl = $this->getUrl('orocrmpro_api_outlook_get_search');
    }

    public function testEmailSearch()
    {
        $entityClasses = [];

        // No search string - should return all entities:
        // 1 - User, 1 - Account, 4 - Contacts, 1 - Customer, 1 - Lead, 1 - Opportunity
        $this->client->request('GET', $this->baseUrl);
        $entities = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertNotEmpty($entities);
        $this->assertCount(9, $entities);
        foreach ($entities as $entity) {
            if (!isset($entityClasses[$entity['entity']])) {
                $entityClasses[$entity['entity']] = $entity['entity'];
            }
        }
        // Check using multiple entities in from filter. Should return all entities.
        $this->client->request('GET', $this->baseUrl . sprintf('?from=%s', implode(',', $entityClasses)));
        $this->assertCount(count($entities), $this->getJsonResponseContent($this->client->getResponse(), 200));

        // Check search by Contact name. Should return all related entities:
        // 1 - Account, 1 - Contact, 1 - Customer, 1 - Lead, 1 - Opportunity
        $this->client->request(
            'GET',
            $this->baseUrl . sprintf('?search=%s', LoadOutlookEntitiesData::FIRST_CONTACT_NAME)
        );
        $entities = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertCount(5, $entities);
        $entities = array_filter(
            $entities,
            function ($entity) {
                return $entity['entity'] === 'OroCRM\Bundle\ContactBundle\Entity\Contact';
            }
        );
        $entity = reset($entities);
        $this->assertContains(LoadOutlookEntitiesData::FIRST_CONTACT_NAME, $entity['title']);

        // Check search by Contact name filtered by Contact entity only. Should return only one Contact entity.
        $this->client->request(
            'GET',
            $this->baseUrl
            . sprintf('?search=%s&from=%s', LoadOutlookEntitiesData::FIRST_CONTACT_NAME, $entity['entity'])
        );
        $entities = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertCount(1, $entities);

        // Check filtering by Contact entity.
        $this->client->request('GET', $this->baseUrl . sprintf('?from=%s', $entity['entity']));
        $entities = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertCount(4, $entities);

        // Check searching by non-existing entity title. Should return no results.
        $this->client->request('GET', $this->baseUrl . sprintf('?search=%s&page=1', 'NonExistentEntityTitle'));
        $entities = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertEmpty($entities);
    }

    public function testEmailSearchWithPaging()
    {
        // 2 - Contacts, 1 - Account
        $this->client->request(
            'GET',
            $this->baseUrl . '?page=2&limit=5',
            [],
            [],
            ['HTTP_X-Include' => 'totalCount']
        );
        $response = $this->client->getResponse();
        $entities = $this->getJsonResponseContent($response, 200);
        $this->assertCount(4, $entities);
        $this->assertEquals(9, $response->headers->get('X-Include-Total-Count'));
    }
}
