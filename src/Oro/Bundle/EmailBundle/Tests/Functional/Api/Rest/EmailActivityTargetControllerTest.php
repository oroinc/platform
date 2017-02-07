<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\Api\Rest;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class EmailActivityTargetControllerTest extends WebTestCase
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

    public function testGetAllActivityTargetTypes()
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_activity_target_all_types')
        );
        $entities = $this->getJsonResponseContent($this->client->getResponse(), 200);
        // at least User entity should be returned
        $this->assertTrue(count($entities) >= 1);
    }

    public function testGetActivityTypes()
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_activity_target_activity_types', ['entity' => 'users'])
        );
        $entities = $this->getJsonResponseContent($this->client->getResponse(), 200);
        // at least email activity should be returned
        $this->assertTrue(count($entities) >= 1);
    }

    public function testGetActivities()
    {
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_api_get_activity_target_activities',
                ['entity' => 'users', 'id' => $this->getReference('user_1')->getId()]
            )
        );
        $entities = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertCount(2, $entities);
    }
}
