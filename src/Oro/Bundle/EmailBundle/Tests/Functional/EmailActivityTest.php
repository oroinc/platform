<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional;

use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures\LoadAdminOwnerEmailData;
use Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures\LoadEmailData;
use Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures\LoadUserWithUserRoleData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * @dbIsolationPerTest
 */
class EmailActivityTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader());
    }

    public function testActivityDateIsNotUpdatedAfterUpdateEntity()
    {
        $this->loadFixtures(['Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures\LoadEmailData']);
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $email = $this->getReference('email_1');
        $originalSentAt = $email->getSentAt();
        $email->setSubject('My Web Store Introduction Changed');
        $em->flush($email);

        $activityList = $em
            ->getRepository(ActivityList::ENTITY_NAME)
            ->findOneBy(
                [
                    'relatedActivityClass' => 'Oro\Bundle\EmailBundle\Entity\Email',
                    'relatedActivityId' => $email->getId()
                ]
            );

        $this->assertEquals($originalSentAt, $activityList->getUpdatedAt());
    }

    public function testGetSimpleUserActivityListByAdmin()
    {
        $this->loadFixtures([LoadEmailData::class]);

        $emailOwner = $this->getReference('simple_user');
        $routingHelper = $this->getContainer()->get('oro_entity.routing_helper');
        $url = $this->getUrl(
            'oro_activity_list_api_get_list',
            [
                'entityClass' => $routingHelper->getUrlSafeClassName(User::class),
                'entityId'    => $emailOwner->getId()
            ]
        );
        $this->client->request('GET', $url);

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertEquals(10, $result['count']);
    }

    public function testGetAdminActivityListByAdmin()
    {
        $this->loadFixtures([LoadAdminOwnerEmailData::class]);

        $admin = $this->getContainer()->get('doctrine')->getRepository(User::class)->findOneByUsername('admin');
        $routingHelper = $this->getContainer()->get('oro_entity.routing_helper');
        $url = $this->getUrl(
            'oro_activity_list_api_get_list',
            [
                'entityClass' => $routingHelper->getUrlSafeClassName(User::class),
                'entityId'    => $admin->getId()
            ]
        );
        $this->client->request('GET', $url);

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertEquals(10, $result['count']);
    }

    public function testGetAdminActivityListByRestrictedUser()
    {
        $this->loadFixtures([LoadAdminOwnerEmailData::class]);
        $this->loadFixtures([LoadUserWithUserRoleData::class]);

        $admin = $this->getContainer()->get('doctrine')->getRepository(User::class)->findOneByUsername('admin');
        $routingHelper = $this->getContainer()->get('oro_entity.routing_helper');
        $url = $this->getUrl(
            'oro_activity_list_api_get_list',
            [
                'entityClass' => $routingHelper->getUrlSafeClassName(User::class),
                'entityId'    => $admin->getId()
            ]
        );

        $user = $this->getReference('limited_user');
        $this->client->request(
            'GET',
            $url,
            [],
            [],
            $this->generateWsseAuthHeader($user->getUsername(), $user->getUsername())
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertEquals(0, $result['count']);
    }
}
