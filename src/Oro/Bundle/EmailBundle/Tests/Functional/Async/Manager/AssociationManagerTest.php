<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\Async\Manager;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\ActivityBundle\Event\ActivityEvent;
use Oro\Bundle\ActivityBundle\Event\Events;
use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\EmailBundle\Async\Manager\AssociationManager;
use Oro\Bundle\EmailBundle\Async\Topic\AddEmailAssociationsTopic;
use Oro\Bundle\EmailBundle\Async\Topic\UpdateEmailOwnerAssociationsTopic;
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
    private $eventDispatcher;

    /** @var AssociationManager */
    private $manager;

    /** @var array */
    private $dispatched = [];

    protected function setUp(): void
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
    protected function tearDown(): void
    {
        $this->dispatched = [];
    }

    public function testProcessUpdateAllEmailOwnersAsync()
    {
        $this->manager->processUpdateAllEmailOwners();

        /* @var User $user */
        $owner = $this->getReference('simple_user');

        $this->assertMessageSent(
            UpdateEmailOwnerAssociationsTopic::getName(),
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

        /* @var User $user */
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

        $this->assertMessagesEmpty(UpdateEmailOwnerAssociationsTopic::getName());
    }

    public function testProcessUpdateEmailOwnerAsync()
    {
        /* @var User $user */
        $owner = $this->getReference('simple_user');

        $ids = [];
        for ($i = 1; $i <= 10; $i++) {
            $ids[] = $this->getReference('email_' . $i)->getId();
        }

        $this->manager->processUpdateEmailOwner(User::class, [$owner->getId()]);

        $this->assertMessageSent(
            AddEmailAssociationsTopic::getName(),
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

        /* @var User $user */
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

        $this->assertMessagesEmpty(AddEmailAssociationsTopic::getName());
    }

    private function assertDispatchedEvents(array $expected)
    {
        self::assertEqualsCanonicalizing($expected, $this->dispatched);
    }

    /**
     * @return Email[]
     */
    private function getTestEmails(): array
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
    private function getActivityManager()
    {
        return $this->getContainer()->get('oro_activity.manager');
    }

    /**
     * @param string $className
     * @return EntityManager
     */
    private function getManagerForClass($className)
    {
        return $this->getContainer()->get('doctrine')->getManagerForClass($className);
    }
}
