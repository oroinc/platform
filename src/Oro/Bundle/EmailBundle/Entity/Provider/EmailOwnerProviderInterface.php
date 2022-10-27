<?php

namespace Oro\Bundle\EmailBundle\Entity\Provider;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EmailBundle\Entity\EmailOwnerInterface;

/**
 * Defines an interface of an email address owner provider.
 */
interface EmailOwnerProviderInterface
{
    /**
     * Gets FQCN of an email address owner represented by this provider.
     * The returned class must implement {@see \Oro\Bundle\EmailBundle\Entity\EmailOwnerInterface}.
     */
    public function getEmailOwnerClass(): string;

    /**
     * Finds an entity object that is an owner of the given email address.
     * The returned object must be an instance of the class specified by the {@see getEmailOwnerClass()} method.
     */
    public function findEmailOwner(EntityManagerInterface $em, string $email): ?EmailOwnerInterface;

    /**
     * Gets the list of organization IDs where the given email address is used.
     */
    public function getOrganizations(EntityManagerInterface $em, string $email): array;

    /**
     * Gets the list of email addresses for the given organization.
     */
    public function getEmails(EntityManagerInterface $em, int $organizationId): iterable;
}
