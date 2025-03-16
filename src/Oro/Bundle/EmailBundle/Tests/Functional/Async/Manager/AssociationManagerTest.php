<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\Async\Manager;

use Doctrine\ORM\EntityManagerInterface;
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

/**
 * @dbIsolationPerTest
 */
class AssociationManagerTest extends WebTestCase
{
    use MessageQueueAssertTrait;

    private array $dispatched = [];
    private AssociationManager $manager;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadEmailData::class]);

        self::getContainer()->get('event_dispatcher')->addListener(
            Events::ADD_ACTIVITY,
            function (ActivityEvent $event) {
                $this->dispatched[] = sprintf('%d-%d', $event->getActivity()->getId(), $event->getTarget()->getId());
            }
        );

        $this->manager = self::getContainer()->get('oro_email.async.manager.association_manager');
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->dispatched = [];
    }

    private function getActivityManager(): ActivityManager
    {
        return self::getContainer()->get('oro_activity.manager');
    }

    private function getManagerForClass(string $className): EntityManagerInterface
    {
        return self::getContainer()->get('doctrine')->getManagerForClass($className);
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

    private function assertDispatchedEvents(array $expected): void
    {
        self::assertEqualsCanonicalizing($expected, $this->dispatched);
    }

    public function testProcessUpdateAllEmailOwnersAsync(): void
    {
        $this->manager->processUpdateAllEmailOwners();

        /* @var User $user */
        $owner = $this->getReference('simple_user');

        self::assertMessageSent(
            UpdateEmailOwnerAssociationsTopic::getName(),
            [
                'ownerClass' => User::class,
                'ownerIds' => [$owner->getId()],
            ]
        );

        $this->assertDispatchedEvents([]);
    }

    public function testProcessUpdateAllEmailOwnersSync(): void
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

        self::assertMessagesEmpty(UpdateEmailOwnerAssociationsTopic::getName());
    }

    public function testProcessUpdateEmailOwnerAsync(): void
    {
        /* @var User $user */
        $owner = $this->getReference('simple_user');

        $ids = [];
        for ($i = 1; $i <= 10; $i++) {
            $ids[] = $this->getReference('email_' . $i)->getId();
        }

        $this->manager->processUpdateEmailOwner(User::class, [$owner->getId()]);

        self::assertMessageSent(
            AddEmailAssociationsTopic::getName(),
            [
                'targetClass' => User::class,
                'targetId' => $owner->getId(),
                'emailIds' => $ids,
            ]
        );

        $this->assertDispatchedEvents([]);
    }

    public function testProcessUpdateEmailOwnerSync(): void
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

        self::assertMessagesEmpty(AddEmailAssociationsTopic::getName());
    }
}
