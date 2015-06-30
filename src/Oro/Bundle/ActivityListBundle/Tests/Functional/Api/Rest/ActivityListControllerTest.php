<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Functional\Api\Rest;

use Oro\Bundle\TestFrameworkBundle\Entity\TestActivity;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class ActivityListControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader());
        $this->loadFixtures(['Oro\Bundle\ActivityListBundle\Tests\Functional\Fixture\LoadActivityData'], true);
    }

    public function testGetList()
    {
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_activity_list_api_get_list',
                [
                    'entityClass' => 'Oro_Bundle_TestFrameworkBundle_Entity_TestActivityTarget',
                    'entityId'    => $this->getReference('test_activity_target_1')->getId()
                ]
            )
        );
        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertCount(2, $result['data']);
        $this->assertEquals(2, $result['count']);
    }

    public function testGetActivityListActivityListItem()
    {
        /** @var TestActivity $activity */
        $activity = $this->getReference('test_activity_1');
        $id       = $activity->getId();
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_api_get_activitylist_activity_list_item',
                ['entityId' => $id]
            )
        );
        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertArrayIntersectEquals(
            ['id' => $id, 'subject' => $activity->getMessage(), 'description' => $activity->getDescription()],
            $result
        );
    }

    public function testGetActivityListActivityListOption()
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_activitylist_activity_list_option')
        );
        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertArrayHasKey('Oro_Bundle_TestFrameworkBundle_Entity_TestActivity', $result);
    }
}
