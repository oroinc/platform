<?php

namespace Oro\Bundle\EmailBundle\Entity\Provider;

use Doctrine\ORM\EntityManager;
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
     *
     * @param EntityManager $em
     * @param string $email
     *
     * @return EmailOwnerInterface
     */
    public function findEmailOwner(EntityManager $em, $email)
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
     * @param EntityManager $em
     * @param string $email
     *
     * @return EmailOwnerInterface[]
     */
    public function findEmailOwners(EntityManager $em, $email)
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
     *
     * @param EntityManager $em
     * @param string $email
     *
     * @return array
     */
    public function getOrganizations(EntityManager $em, $email)
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
     *
     * @param EntityManager $em
     * @param int $organizationId
     *
     * @return iterable
     */
    public function getEmails(EntityManager $em, $organizationId)
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
