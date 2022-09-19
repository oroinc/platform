<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional;

use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures\LoadEmailData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;

class EmailActivityTestForPrivateEmails extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], $this->generateWsseAuthHeader());
        $this->loadFixtures([LoadEmailData::class]);
    }

    public function testGetActivityListData(): void
    {
        $email = $this->getReference('email_1');
        $em = self::getContainer()->get('doctrine.orm.entity_manager');

        $activityList = $em->getRepository(ActivityList::class)
            ->findOneBy(['relatedActivityClass' => Email::class, 'relatedActivityId' => $email->getId()]);

        $this->assertNull($activityList);
    }

    public function testGetSimpleUserActivityListByAdmin(): void
    {
        $emailOwner = $this->getReference('simple_user');
        $routingHelper = self::getContainer()->get('oro_entity.routing_helper');
        $url = $this->getUrl(
            'oro_activity_list_api_get_list',
            [
                'entityClass' => $routingHelper->getUrlSafeClassName(User::class),
                'entityId'    => $emailOwner->getId()
            ]
        );
        $this->client->request('GET', $url);

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertEquals(0, $result['count']);
    }

    public function testGetAdminActivityListByAdmin(): void
    {
        $this->initClient([], $this->generateWsseAuthHeader('simple_user', 'simple_password'));

        $user = $this->getReference('simple_user');
        $routingHelper = $this->getContainer()->get('oro_entity.routing_helper');
        $url = $this->getUrl(
            'oro_activity_list_api_get_list',
            [
                'entityClass' => $routingHelper->getUrlSafeClassName(User::class),
                'entityId'    => $user->getId()
            ]
        );
        $this->client->request('GET', $url);

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertEquals(0, $result['count']);
    }
}
