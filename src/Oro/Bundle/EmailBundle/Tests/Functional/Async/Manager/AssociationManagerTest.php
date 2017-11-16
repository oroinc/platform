<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\Async\Manager;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\ActivityBundle\Event\ActivityEvent;
use Oro\Bundle\ActivityBundle\Event\Events;
use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\EmailBundle\Async\Manager\AssociationManager;
use Oro\Bundle\EmailBundle\Async\Topics;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures\LoadEmailData;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueAssertTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @dbIsolationPerTest
 */
class AssociationManagerTest extends WebTestCase
{
    use MessageQueueAssertTrait;

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var AssociationManager */
    protected $manager;

    /** @var array */
    protected $dispatched = [];

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures([
            LoadEmailData::class,
        ]);

        $this->eventDispatcher = $this->getContainer()->get('event_dispatcher');
        $this->eventDispatcher->addListener(
            Events::ADD_ACTIVITY,
            function (ActivityEvent $event) {
                $this->dispatched[] = sprintf('%d-%d', $event->getActivity()->getId(), $event->getTarget()->getId());
            }
        );

        $this->manager = $this->getContainer()->get('oro_email.async.manager.association_manager');
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown()
    {
        $this->dispatched = [];
    }

    public function testProcessUpdateAllEmailOwnersAsync()
    {
        $this->manager->processUpdateAllEmailOwners();

        /* @var $user User */
        $owner = $this->getReference('simple_user');

        $this->assertMessageSent(
            Topics::UPDATE_EMAIL_OWNER_ASSOCIATIONS,
            [
                'ownerClass' => User::class,
                'ownerIds' => [$owner->getId()],
            ]
        );

        $this->assertDispatchedEvents([]);
    }

    public function testProcessUpdateAllEmailOwnersSync()
    {
        $this->manager->setQueued(false);

        /* @var $user User */
        $owner = $this->getReference('simple_user');

        $activityManager = $this->getActivityManager();
        foreach ($this->getTestEmails() as $email) {
            $activityManager->removeActivityTarget($email, $owner);
        }
        $this->getManagerForClass(Email::class)->flush();

        $this->manager->processUpdateAllEmailOwners();

        $expected = [];
        foreach ($this->getTestEmails() as $email) {
            $expected[] = sprintf('%d-%d', $email->getId(), $owner->getId());
        }

        $this->assertDispatchedEvents($expected);

        $this->dispatched = [];
        $this->manager->processUpdateAllEmailOwners();

        $this->assertDispatchedEvents([]);

        $this->assertMessagesEmpty(Topics::UPDATE_EMAIL_OWNER_ASSOCIATIONS);
    }

    public function testProcessUpdateEmailOwnerAsync()
    {
        /* @var $user User */
        $owner = $this->getReference('simple_user');

        $ids = [];
        for ($i = 1; $i <= 10; $i++) {
            $ids[] = $this->getReference('email_' . $i)->getId();
        }

        $this->manager->processUpdateEmailOwner(User::class, [$owner->getId()]);

        $this->assertMessageSent(
            Topics::ADD_ASSOCIATION_TO_EMAILS,
            [
                'targetClass' => User::class,
                'targetId' => $owner->getId(),
                'emailIds' => $ids,
            ]
        );

        $this->assertDispatchedEvents([]);
    }

    public function testProcessUpdateEmailOwnerSync()
    {
        $this->manager->setQueued(false);

        /* @var $user User */
        $owner = $this->getReference('simple_user');

        $activityManager = $this->getActivityManager();
        foreach ($this->getTestEmails() as $email) {
            $activityManager->removeActivityTarget($email, $owner);
        }
        $this->getManagerForClass(Email::class)->flush();

        $this->manager->processUpdateEmailOwner(User::class, [$owner->getId()]);

        $expected = [];
        foreach ($this->getTestEmails() as $email) {
            $expected[] = sprintf('%d-%d', $email->getId(), $owner->getId());
        }

        $this->assertDispatchedEvents($expected);

        $this->dispatched = [];
        $this->manager->processUpdateEmailOwner(User::class, [$owner->getId()]);

        $this->assertDispatchedEvents([]);

        $this->assertMessagesEmpty(Topics::ADD_ASSOCIATION_TO_EMAILS);
    }

    /**
     * @param array $expected
     */
    protected function assertDispatchedEvents(array $expected)
    {
        sort($this->dispatched);

        $this->assertEquals($expected, $this->dispatched);
    }

    /**
     * @return array|Email[]
     */
    protected function getTestEmails()
    {
        $emails = [];
        for ($i = 1; $i <= 10; $i++) {
            $emails[] = $this->getReference('email_' . $i);
        }

        return $emails;
    }

    /**
     * @return ActivityManager
     */
    protected function getActivityManager()
    {
        return $this->getContainer()->get('oro_activity.manager');
    }

    /**
     * @param string $className
     * @return EntityManager
     */
    protected function getManagerForClass($className)
    {
        return $this->getContainer()->get('doctrine')->getManagerForClass($className);
    }
}
