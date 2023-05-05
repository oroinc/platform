<?php

namespace Oro\Bundle\EntityBundle\Tests\Functional\EventListener;

use Doctrine\ORM\Proxy\Proxy;
use Oro\Bundle\EntityBundle\Event\PreloadEntityEvent;
use Oro\Bundle\EntityBundle\Tests\Functional\DataFixtures\LoadUserData;
use Oro\Bundle\EntityBundle\Tests\Functional\DataFixtures\LoadUserEmailData;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\Email;
use Oro\Bundle\UserBundle\Entity\User;

class DefaultPreloadingListenerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures(
            [
                LoadUserData::class,
                LoadUserEmailData::class,
            ]
        );
    }

    public function testOnPreloadWhenNotExitingEntity(): void
    {
        $entityManager = $this->getContainer()->get('doctrine')->getManagerForClass(User::class);

        // Ensure entity manager is cleared and will not use already loaded entities.
        $entityManager->clear();

        /** @var User $user */
        $user = $entityManager->getReference(User::class, 999999);

        $this->assertTrue($user instanceof Proxy && !$user->__isInitialized());

        $event = new PreloadEntityEvent(
            [$user],
            [
                'organizations' => [],
                'emails' => [],
                'owner' => [],
            ],
            []
        );

        $this->getContainer()->get('oro_entity.tests.event_listener.user_preloading')->onPreload($event);

        // Entity must stay uninitialized because it does not exist.
        $this->assertTrue($user instanceof Proxy && !$user->__isInitialized());
    }

    public function testOnPreloadWhenIsNotInitialized(): void
    {
        // Ensure entity manager is cleared and will not use already loaded entities.
        $this->getContainer()->get('doctrine')->getManagerForClass(User::class)->clear();

        /** @var User $user */
        $user = $this->getReference('simple_user');

        $this->assertTrue($user instanceof Proxy && !$user->__isInitialized());

        $event = new PreloadEntityEvent(
            [$user],
            [
                'organizations' => [],
                'emails' => [],
                'owner' => [],
            ],
            []
        );

        $this->getContainer()->get('oro_entity.tests.event_listener.user_preloading')->onPreload($event);

        // Checks many-to-many relation is initialized.
        $this->assertTrue($user->getOrganizations()->isInitialized());
        $this->assertNotProxyOrInitialized($user->getOrganizations()[0], Organization::class);

        // Checks one-to-many relation is initialized.
        $this->assertTrue($user->getEmails()->isInitialized());
        $this->assertNotProxyOrInitialized($user->getEmails()[0], Email::class);

        // Checks to-one relation is initialized.
        $this->assertNotProxyOrInitialized($user->getOwner(), BusinessUnit::class);
    }

    public function testOnPreloadWhenInitialized(): void
    {
        // Ensure entity manager is cleared and will not use already loaded entities.
        $this->getContainer()->get('doctrine')->getManagerForClass(User::class)->clear();

        /** @var User|Proxy $user */
        $user = $this->getReference('simple_user');

        // Initializes proxy.
        $user->__load();

        $this->assertTrue($user instanceof Proxy && $user->__isInitialized());
        $this->assertFalse($user->getOrganizations()->isInitialized());
        $this->assertFalse($user->getEmails()->isInitialized());
        $this->assertProxyAndNotInitialized($user->getOwner());

        $event = new PreloadEntityEvent(
            [$user],
            [
                'organizations' => [],
                'emails' => [],
                'owner' => [],
            ],
            []
        );

        $this->getContainer()->get('oro_entity.tests.event_listener.user_preloading')->onPreload($event);

        // Checks many-to-many relation is initialized.
        $this->assertTrue($user->getOrganizations()->isInitialized());
        $this->assertNotProxyOrInitialized($user->getOrganizations()[0], Organization::class);

        // Checks one-to-many relation is initialized.
        $this->assertTrue($user->getEmails()->isInitialized());
        $this->assertNotProxyOrInitialized($user->getEmails()[0], Email::class);

        // Checks to-one relation is initialized.
        $this->assertNotProxyOrInitialized($user->getOwner(), BusinessUnit::class);
    }

    private function assertProxyAndNotInitialized(object $value): void
    {
        $this->assertTrue($value instanceof Proxy && !$value->__isInitialized());
    }

    private function assertNotProxyOrInitialized(object $value, string $expectedClass): void
    {
        if ($value instanceof Proxy) {
            $this->assertTrue($value->__isInitialized());
        } else {
            $this->assertInstanceOf($expectedClass, $value);
        }
    }
}
