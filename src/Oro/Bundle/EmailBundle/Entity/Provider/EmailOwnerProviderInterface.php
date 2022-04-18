<?php

namespace Oro\Bundle\EmailBundle\Entity\Provider;

use Doctrine\ORM\EntityManager;
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
    public function getEmailOwnerClass();

    /**
     * Finds an entity object that is an owner of the given email address.
     * The returned object must be an instance of the class specified by the {@see getEmailOwnerClass()} method.
     *
     * @param EntityManager $em
     * @param string $email
     *
     * @return EmailOwnerInterface
     */
    public function findEmailOwner(EntityManager $em, $email);

    /**
     * Gets the list of organization IDs where the given email address is used.
     *
     * @param EntityManager $em
     * @param string $email
     *
     * @return array
     */
    public function getOrganizations(EntityManager $em, $email);

    /**
     * Gets the list of email addresses for the given organization.
     *
     * @param EntityManager $em
     * @param int $organizationId
     *
     * @return iterable
     */
    public function getEmails(EntityManager $em, $organizationId);
}
