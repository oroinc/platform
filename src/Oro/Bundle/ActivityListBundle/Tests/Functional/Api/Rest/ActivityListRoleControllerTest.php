<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Functional\Api\Rest;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * @dbIsolation
 */
class ActivityListRoleControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader(), true);
        $this->loadFixtures([
            'Oro\Bundle\ActivityListBundle\Tests\Functional\DataFixtures\LoadActivityData',
            'Oro\Bundle\ActivityListBundle\Tests\Functional\DataFixtures\LoadUserData'
        ]);
    }

    /**
     * Test to verify access for user who does not have access to activity other user
     */
    public function testGetListForUserWithOutPermissions()
    {
        $this->markTestSkipped("Test skipped. User wssi do not work. Test entity ACL do not work.");
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
        $this->assertCount(0, $result['data']);
        $this->assertEquals(0, $result['count']);
    }
}
