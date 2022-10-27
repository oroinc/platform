<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Functional\Api\Rest;

use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\ActivityListBundle\Tests\Functional\DataFixtures\LoadActivityData;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivity;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ActivityListControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], $this->generateWsseAuthHeader());
        $this->loadFixtures([LoadActivityData::class]);
    }

    public function testGetList()
    {
        $this->client->jsonRequest(
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
        $activityId       = $activity->getId();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $activityListId = $em->getRepository(ActivityList::class)
            ->createQueryBuilder('list')
            ->select('list.id')
            ->where('list.relatedActivityClass = :relatedActivityClass')
            ->andWhere('list.relatedActivityId = :relatedActivityId')
            ->setParameter('relatedActivityClass', TestActivity::class)
            ->setParameter('relatedActivityId', $activityId)
            ->getQuery()
            ->getSingleScalarResult();

        $this->client->jsonRequest(
            'GET',
            $this->getUrl(
                'oro_api_get_activitylist_activity_list_item',
                ['entityId' => $activityListId]
            )
        );
        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertArrayIntersectEquals(
            [
                'id' => $activityListId,
                'relatedActivityId' => $activityId,
                'subject' => $activity->getMessage(),
                'description' => $activity->getDescription()
            ],
            $result
        );
    }

    public function testGetActivityListActivityListOption()
    {
        $this->client->jsonRequest(
            'GET',
            $this->getUrl('oro_api_get_activitylist_activity_list_option')
        );
        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertArrayHasKey('Oro_Bundle_TestFrameworkBundle_Entity_TestActivity', $result);
    }
}
