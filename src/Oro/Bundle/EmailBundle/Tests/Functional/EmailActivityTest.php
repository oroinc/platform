<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional;

use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\EmailBundle\Entity\Email;
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
    use PublicEmailOwnerTrait;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateWsseAuthHeader());
        self::addPublicEmailOwner(User::class);
    }

    public function testActivityDateIsNotUpdatedAfterUpdateEntity()
    {
        $this->loadFixtures([LoadEmailData::class]);

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $email = $this->getReference('email_1');
        $originalSentAt = $email->getSentAt();
        $email->setSubject('My Web Store Introduction Changed');
        $em->flush($email);

        /** @var ActivityList $activityList */
        $activityList = $em->getRepository(ActivityList::class)
            ->findOneBy(['relatedActivityClass' => Email::class, 'relatedActivityId' => $email->getId()]);

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
        $this->loadFixtures([LoadEmailData::class]);
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
