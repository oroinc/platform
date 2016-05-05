<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Functional;

use Oro\Bundle\TestFrameworkBundle\Entity\TestActivity;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class ControllersTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures(['Oro\Bundle\ActivityListBundle\Tests\Functional\DataFixtures\LoadActivityData']);
    }

    public function testWidget()
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'oro_activity_list_widget_activities',
                [
                    'entityClass' => 'Oro_Bundle_TestFrameworkBundle_Entity_TestActivityTarget',
                    'entityId'    => $this->getReference('test_activity_target_1')->getId(),
                    '_widgetContainer' => 'widget'
                ]
            )
        );
        $result  = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $content = $result->getContent();
        /** @var TestActivity $activity1 */
        $activity1 = $this->getReference('test_activity_1');
        /** @var TestActivity $activity2 */
        $activity2 = $this->getReference('test_activity_2');
        $this->assertContains($activity1->getMessage(), $content);
        $this->assertContains($activity2->getMessage(), $content);
        $this->assertCount(1, $crawler->filter('div.widget-content.activity-list'));
    }
}
