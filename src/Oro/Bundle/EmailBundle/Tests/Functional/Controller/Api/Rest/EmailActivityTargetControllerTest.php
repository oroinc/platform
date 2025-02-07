<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\Controller\Api\Rest;

use Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures\LoadEmailActivityData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class EmailActivityTargetControllerTest extends WebTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateApiAuthHeader());
        $this->loadFixtures([LoadEmailActivityData::class]);
    }

    public function testGetAllActivityTargetTypes()
    {
        $this->client->jsonRequest(
            'GET',
            $this->getUrl('oro_api_get_activity_target_all_types')
        );
        $entities = $this->getJsonResponseContent($this->client->getResponse(), 200);
        // at least User entity should be returned
        $this->assertNotEmpty($entities);
    }

    public function testGetActivityTypes()
    {
        $this->client->jsonRequest(
            'GET',
            $this->getUrl('oro_api_get_activity_target_activity_types', ['entity' => 'users'])
        );
        $entities = $this->getJsonResponseContent($this->client->getResponse(), 200);
        // at least email activity should be returned
        $this->assertNotEmpty($entities);
    }

    public function testGetActivities()
    {
        $this->client->jsonRequest(
            'GET',
            $this->getUrl(
                'oro_api_get_activity_target_activities',
                ['entity' => 'users', 'id' => $this->getReference('user_1')->getId()]
            )
        );
        $entities = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertCount(3, $entities);
    }
}
