<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional;

use Oro\Bundle\EmailBundle\Tests\Functional\Stub\PublicEmailOwnerProviderStub;

/**
 * Provides methods to substitute the list of public email address owners in functional tests.
 * It is expected that this trait will be used in classes
 * derived from {@see \Oro\Bundle\TestFrameworkBundle\Test\WebTestCase}.
 */
trait PublicEmailOwnerTrait
{
    private static function addPublicEmailOwner(string $ownerClass): void
    {
        self::getContainer()->get('oro_platform.optional_listeners.manager')
            ->enableListener('oro_email.listener.entity_listener');
        self::getPublicEmailOwnerProvider()->addPublicEmailOwner($ownerClass);
    }

    private static function removePublicEmailOwner(string $ownerClass): void
    {
        self::getContainer()->get('oro_platform.optional_listeners.manager')
            ->enableListener('oro_email.listener.entity_listener');
        self::getPublicEmailOwnerProvider()->removePublicEmailOwner($ownerClass);
    }

    /**
     * @beforeResetClient
     */
    public static function resetPublicEmailOwners(): void
    {
        self::getContainer()->get('oro_platform.optional_listeners.manager')
            ->disableListener('oro_email.listener.entity_listener');
        self::getPublicEmailOwnerProvider()->resetEmailOwners();
    }

    private static function getPublicEmailOwnerProvider(): PublicEmailOwnerProviderStub
    {
        $provider = self::getContainer()->get('oro_email.public_email_owner_provider');
        if (!$provider instanceof PublicEmailOwnerProviderStub) {
            throw new \LogicException(sprintf(
                'The service "oro_email.public_email_owner_provider" should be instance of "%s", given "%s".',
                PublicEmailOwnerProviderStub::class,
                get_class($provider)
            ));
        }

        return $provider;
    }
}
