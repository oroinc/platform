<?php

namespace Oro\Bundle\EntityBundle\Tests\Functional\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Proxy\Proxy;
use Oro\Bundle\EntityBundle\Event\PreloadEntityEvent;
use Oro\Bundle\EntityBundle\EventListener\DefaultPreloadingListener;
use Oro\Bundle\EntityBundle\Tests\Functional\DataFixtures\LoadUserData;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\Email;
use Oro\Bundle\UserBundle\Entity\User;

class DefaultPreloadingListenerTest extends WebTestCase
{
    private DefaultPreloadingListener $listener;

    private EntityManagerInterface $entityManager;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());

        $this->listener = self::getContainer()->get('oro_entity.tests.event_listener.user_preloading');
        $this->entityManager = self::getContainer()->get('doctrine')->getManagerForClass(User::class);
        $this->listener->setStopPropagation(false);
    }

    public function testOnPreloadWhenNotExitingEntity(): void
    {
        // Ensure entity manager is cleared and will not use already loaded entities.
        $this->entityManager->clear();

        /** @var User $user */
        $user = $this->entityManager->getReference(User::class, 999999);

        $event = new PreloadEntityEvent(
            [$user],
            [
                'organizations' => [],
                'emails' => [],
                'owner' => [],
            ],
            []
        );

        $this->listener->onPreload($event);

        // Entity must stay uninitialized because it does not exist.
        self::assertTrue($user instanceof Proxy && !$user->__isInitialized());
    }

    public function testOnPreloadWhenIsNotInitialized(): void
    {
        // Ensure entity manager is cleared and will not use already loaded entities.
        $this->entityManager->clear();

        $userId = $this->getExistingUserId();

        /** @var User $user */
        $user = $this->entityManager->getReference(User::class, $userId);

        $event = new PreloadEntityEvent(
            [$user],
            [
                'organizations' => [],
                'emails' => [],
                'owner' => [],
            ],
            []
        );

        $this->listener->onPreload($event);

        // Checks many-to-many relation is initialized.
        self::assertTrue($user->getOrganizations()->isInitialized());
        foreach ($user->getOrganizations() as $organization) {
            $this->assertNotProxyOrInitialized($organization, Organization::class);
        }

        // Checks one-to-many relation is initialized.
        self::assertTrue($user->getEmails()->isInitialized());
        foreach ($user->getEmails() as $email) {
            $this->assertNotProxyOrInitialized($email, Email::class);
        }

        // Checks to-one relation is initialized.
        $this->assertNotProxyOrInitialized($user->getOwner(), BusinessUnit::class);
    }

    public function testOnPreloadWhenInitialized(): void
    {
        // Ensure entity manager is cleared and will not use already loaded entities.
        $this->entityManager->clear();

        $userId = $this->getExistingUserId();

        /** @var User $user */
        $user = $this->entityManager->getReference(User::class, $userId);

        // Force-load the entity to verify the initialized-entity path.
        $this->entityManager->refresh($user);

        $event = new PreloadEntityEvent(
            [$user],
            [
                'organizations' => [],
                'emails' => [],
                'owner' => [],
            ],
            []
        );

        $this->listener->onPreload($event);

        // Checks many-to-many relation is initialized.
        self::assertTrue($user->getOrganizations()->isInitialized());
        foreach ($user->getOrganizations() as $organization) {
            $this->assertNotProxyOrInitialized($organization, Organization::class);
        }

        // Checks one-to-many relation is initialized.
        self::assertTrue($user->getEmails()->isInitialized());
        foreach ($user->getEmails() as $email) {
            $this->assertNotProxyOrInitialized($email, Email::class);
        }

        // Checks to-one relation is initialized.
        $this->assertNotProxyOrInitialized($user->getOwner(), BusinessUnit::class);
    }

    public function testOnPreloadInitializesManyToManyRelationSharedByMultipleOwners(): void
    {
        // Adds a second user (simple_user) that belongs to the same organization as the admin user,
        // so the organization is a many-to-many collection item shared by several owners.
        $this->loadFixtures([LoadUserData::class]);

        $userRepository = $this->entityManager->getRepository(User::class);
        $adminUserId = $userRepository->findOneBy(['username' => 'admin'])->getId();
        $simpleUserId = $userRepository->findOneBy(['username' => 'simple_user'])->getId();

        /** @var Organization $sharedOrganization */
        $sharedOrganization = $this->entityManager->getRepository(Organization::class)->findOneBy([]);
        $sharedOrganizationId = $sharedOrganization->getId();

        // Ensure entity manager is cleared and will not use already loaded entities.
        $this->entityManager->clear();

        /** @var User $adminUser */
        $adminUser = $this->entityManager->getReference(User::class, $adminUserId);
        /** @var User $simpleUser */
        $simpleUser = $this->entityManager->getReference(User::class, $simpleUserId);

        $event = new PreloadEntityEvent([$adminUser, $simpleUser], ['organizations' => []], []);

        $this->listener->onPreload($event);

        // The organization shared by both users must be preloaded into each owner's collection,
        // i.e. a single many-to-many collection item is assigned to every owner returned in "entity_ids".
        self::assertTrue($adminUser->getOrganizations()->isInitialized());
        self::assertContains(
            $sharedOrganizationId,
            $adminUser->getOrganizations()->map(static fn (Organization $o) => $o->getId())->toArray()
        );

        self::assertTrue($simpleUser->getOrganizations()->isInitialized());
        self::assertContains(
            $sharedOrganizationId,
            $simpleUser->getOrganizations()->map(static fn (Organization $o) => $o->getId())->toArray()
        );
    }

    public function testOnPreloadStopsEventPropagationWhenConfigured(): void
    {
        $this->entityManager->clear();
        $userId = $this->getExistingUserId();
        $user = $this->entityManager->getReference(User::class, $userId);

        $event = new PreloadEntityEvent([$user], [], []);

        self::assertFalse($event->isPropagationStopped());

        $this->listener->setStopPropagation(true);
        try {
            $this->listener->onPreload($event);
        } finally {
            $this->listener->setStopPropagation(false);
        }

        self::assertTrue($event->isPropagationStopped());
    }

    private function getExistingUserId(): int
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy([]);

        self::assertNotNull($user);

        return $user->getId();
    }

    private function assertNotProxyOrInitialized(object $value, string $expectedClass): void
    {
        if ($value instanceof Proxy) {
            self::assertTrue($value->__isInitialized());
        } else {
            self::assertInstanceOf($expectedClass, $value);
        }
    }
}
