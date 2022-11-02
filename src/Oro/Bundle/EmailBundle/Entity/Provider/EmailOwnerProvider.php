<?php

namespace Oro\Bundle\EmailBundle\Entity\Provider;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EmailBundle\Entity\EmailOwnerInterface;

/**
 * Provides information about email address owners.
 */
class EmailOwnerProvider
{
    private EmailOwnerProviderStorage $emailOwnerProviderStorage;

    public function __construct(EmailOwnerProviderStorage $emailOwnerProviderStorage)
    {
        $this->emailOwnerProviderStorage = $emailOwnerProviderStorage;
    }

    /**
     * Finds an entity object that is an owner of the given email address.
     */
    public function findEmailOwner(EntityManagerInterface $em, string $email): ?EmailOwnerInterface
    {
        $emailOwner = null;
        foreach ($this->emailOwnerProviderStorage->getProviders() as $provider) {
            $emailOwner = $provider->findEmailOwner($em, $email);
            if ($emailOwner !== null) {
                break;
            }
        }

        return $emailOwner;
    }

    /**
     * Finds entity objects that are owners of the given email address.
     *
     * @param EntityManagerInterface $em
     * @param string $email
     *
     * @return EmailOwnerInterface[]
     */
    public function findEmailOwners(EntityManagerInterface $em, string $email): array
    {
        $emailOwners = [];
        foreach ($this->emailOwnerProviderStorage->getProviders() as $provider) {
            $emailOwner = $provider->findEmailOwner($em, $email);
            if ($emailOwner !== null) {
                $emailOwners[] = $emailOwner;
            }
        }

        return $emailOwners;
    }

    /**
     * Gets the list of organization IDs where the given email address is used.
     * The returned value is an array contains the following data:
     * [email owner class => [organization id, ...], ...].
     */
    public function getOrganizations(EntityManagerInterface $em, string $email): array
    {
        $result = [];
        foreach ($this->emailOwnerProviderStorage->getProviders() as $provider) {
            $organizations = $provider->getOrganizations($em, $email);
            if (!empty($organizations)) {
                $result[$provider->getEmailOwnerClass()] = $organizations;
            }
        }

        return $result;
    }

    /**
     * Returns the list of email address for the given organization.
     * Each returned item is the following array: [email address, email address owner class].
     */
    public function getEmails(EntityManagerInterface $em, int $organizationId): iterable
    {
        foreach ($this->emailOwnerProviderStorage->getProviders() as $provider) {
            $ownerClass = $provider->getEmailOwnerClass();
            $emails = $provider->getEmails($em, $organizationId);
            foreach ($emails as $email) {
                yield [$email, $ownerClass];
            }
        }
    }
}
